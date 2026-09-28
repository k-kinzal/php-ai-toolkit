<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Analysis\AnalysisResult;
use Toolkit\DocGuard\Analysis\DocGuardAnalyzer;
use Toolkit\DocGuard\Analysis\HeadingHunkClassifier;
use Toolkit\DocGuard\Analysis\HeadingSequenceAligner;
use Toolkit\DocGuard\Analysis\HeadingStructureComparator;
use Toolkit\DocGuard\Analysis\UndeclaredDocumentInspector;
use Toolkit\DocGuard\Analysis\Violation;
use Toolkit\DocGuard\Analysis\ViolationFactory;
use Toolkit\DocGuard\Cli\Application;
use Toolkit\DocGuard\Cli\DocGuardAnalysisRunner;
use Toolkit\DocGuard\Cli\DocGuardCliArgumentParser;
use Toolkit\DocGuard\Cli\DocGuardConfigPathResolver;
use Toolkit\DocGuard\Cli\DocGuardGenerateRunner;
use Toolkit\DocGuard\Cli\DocGuardHelpText;
use Toolkit\DocGuard\Cli\DocGuardOutputWriter;
use Toolkit\DocGuard\Cli\DocGuardReporterOverride;
use Toolkit\DocGuard\Config\ConfigKeyValidator;
use Toolkit\DocGuard\Config\ConfigLoader;
use Toolkit\DocGuard\Config\ConfigStringListReader;
use Toolkit\DocGuard\Config\DeclaredHeading;
use Toolkit\DocGuard\Config\DeclaredHeadingReader;
use Toolkit\DocGuard\Config\DocGuardConfig;
use Toolkit\DocGuard\Config\DocumentConfig;
use Toolkit\DocGuard\Config\DocumentConfigReader;
use Toolkit\DocGuard\Config\DocumentListConfigReader;
use Toolkit\DocGuard\Config\ReportConfig;
use Toolkit\DocGuard\Config\ReportConfigReader;
use Toolkit\DocGuard\DocGuardException;
use Toolkit\DocGuard\Filesystem\DocGuardPathResolver;
use Toolkit\DocGuard\Filesystem\MarkdownFileFinder;
use Toolkit\DocGuard\Filesystem\MarkdownFileReader;
use Toolkit\DocGuard\Generation\ConfigGenerator;
use Toolkit\DocGuard\Generation\ConfigYamlWriter;
use Toolkit\DocGuard\Generation\GenerationTargetCollector;
use Toolkit\DocGuard\Markdown\AtxHeadingMatcher;
use Toolkit\DocGuard\Markdown\BlockLineScanner;
use Toolkit\DocGuard\Markdown\BlockMarkerMatcher;
use Toolkit\DocGuard\Markdown\Fence;
use Toolkit\DocGuard\Markdown\FenceMatcher;
use Toolkit\DocGuard\Markdown\Heading;
use Toolkit\DocGuard\Markdown\HeadingParser;
use Toolkit\DocGuard\Markdown\HeadingTextNormalizer;
use Toolkit\DocGuard\Markdown\HtmlBlockMatcher;
use Toolkit\DocGuard\Markdown\LineIndentation;
use Toolkit\DocGuard\Markdown\LineScanner;
use Toolkit\DocGuard\Markdown\MarkdownLineSplitter;
use Toolkit\DocGuard\Markdown\ParserState;
use Toolkit\DocGuard\Markdown\SetextUnderlineMatcher;
use Toolkit\DocGuard\Reporting\AiReporter;
use Toolkit\DocGuard\Reporting\AiReportGuidance;
use Toolkit\DocGuard\Reporting\AiReportSummary;
use Toolkit\DocGuard\Reporting\AiViolationAction;
use Toolkit\DocGuard\Reporting\AiViolationFormatter;
use Toolkit\DocGuard\Reporting\JsonReporter;
use Toolkit\DocGuard\Reporting\Reporter;
use Toolkit\DocGuard\Reporting\ReporterFactory;
use Toolkit\DocGuard\Reporting\TextReporter;
use Toolkit\DocGuard\Reporting\ViolationFieldComparator;
use Toolkit\DocGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\DocGuard\Cli\Application
 * @uses \Toolkit\DocGuard\Reporting\AiReportGuidance
 * @uses \Toolkit\DocGuard\Reporting\AiReportSummary
 * @uses \Toolkit\DocGuard\Reporting\AiReporter
 * @uses \Toolkit\DocGuard\Reporting\AiViolationAction
 * @uses \Toolkit\DocGuard\Reporting\AiViolationFormatter
 * @uses \Toolkit\DocGuard\Analysis\AnalysisResult
 * @uses \Toolkit\DocGuard\Markdown\AtxHeadingMatcher
 * @uses \Toolkit\DocGuard\Markdown\BlockLineScanner
 * @uses \Toolkit\DocGuard\Markdown\BlockMarkerMatcher
 * @uses \Toolkit\DocGuard\Generation\ConfigGenerator
 * @uses \Toolkit\DocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\DocGuard\Config\ConfigLoader
 * @uses \Toolkit\DocGuard\Config\ConfigStringListReader
 * @uses \Toolkit\DocGuard\Generation\ConfigYamlWriter
 * @uses \Toolkit\DocGuard\Config\DeclaredHeading
 * @uses \Toolkit\DocGuard\Config\DeclaredHeadingReader
 * @uses \Toolkit\DocGuard\Cli\DocGuardAnalysisRunner
 * @uses \Toolkit\DocGuard\Analysis\DocGuardAnalyzer
 * @uses \Toolkit\DocGuard\Cli\DocGuardCliArgumentParser
 * @uses \Toolkit\DocGuard\Config\DocGuardConfig
 * @uses \Toolkit\DocGuard\Cli\DocGuardConfigPathResolver
 * @uses \Toolkit\DocGuard\DocGuardException
 * @uses \Toolkit\DocGuard\Cli\DocGuardGenerateRunner
 * @uses \Toolkit\DocGuard\Cli\DocGuardHelpText
 * @uses \Toolkit\DocGuard\Cli\DocGuardOutputWriter
 * @uses \Toolkit\DocGuard\Filesystem\DocGuardPathResolver
 * @uses \Toolkit\DocGuard\Cli\DocGuardReporterOverride
 * @uses \Toolkit\DocGuard\Config\DocumentConfig
 * @uses \Toolkit\DocGuard\Config\DocumentConfigReader
 * @uses \Toolkit\DocGuard\Config\DocumentListConfigReader
 * @uses \Toolkit\DocGuard\Markdown\Fence
 * @uses \Toolkit\DocGuard\Markdown\FenceMatcher
 * @uses \Toolkit\DocGuard\Generation\GenerationTargetCollector
 * @uses \Toolkit\DocGuard\Markdown\Heading
 * @uses \Toolkit\DocGuard\Analysis\HeadingHunkClassifier
 * @uses \Toolkit\DocGuard\Markdown\HeadingParser
 * @uses \Toolkit\DocGuard\Analysis\HeadingSequenceAligner
 * @uses \Toolkit\DocGuard\Analysis\HeadingStructureComparator
 * @uses \Toolkit\DocGuard\Markdown\HeadingTextNormalizer
 * @uses \Toolkit\DocGuard\Markdown\HtmlBlockMatcher
 * @uses \Toolkit\DocGuard\Reporting\JsonReporter
 * @uses \Toolkit\DocGuard\Markdown\LineIndentation
 * @uses \Toolkit\DocGuard\Markdown\LineScanner
 * @uses \Toolkit\DocGuard\Filesystem\MarkdownFileFinder
 * @uses \Toolkit\DocGuard\Filesystem\MarkdownFileReader
 * @uses \Toolkit\DocGuard\Markdown\MarkdownLineSplitter
 * @uses \Toolkit\DocGuard\Markdown\ParserState
 * @uses \Toolkit\DocGuard\Config\ReportConfig
 * @uses \Toolkit\DocGuard\Config\ReportConfigReader
 * @uses \Toolkit\DocGuard\Reporting\Reporter
 * @uses \Toolkit\DocGuard\Reporting\ReporterFactory
 * @uses \Toolkit\DocGuard\Markdown\SetextUnderlineMatcher
 * @uses \Toolkit\DocGuard\Reporting\TextReporter
 * @uses \Toolkit\DocGuard\Analysis\UndeclaredDocumentInspector
 * @uses \Toolkit\DocGuard\Analysis\Violation
 * @uses \Toolkit\DocGuard\Analysis\ViolationFactory
 * @uses \Toolkit\DocGuard\Reporting\ViolationFieldComparator
 * @uses \Toolkit\DocGuard\Reporting\ViolationSorter
 */
#[CoversClass(Application::class)]
#[UsesClass(AiReportGuidance::class)]
#[UsesClass(AiReportSummary::class)]
#[UsesClass(AiReporter::class)]
#[UsesClass(AiViolationAction::class)]
#[UsesClass(AiViolationFormatter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(BlockLineScanner::class)]
#[UsesClass(BlockMarkerMatcher::class)]
#[UsesClass(ConfigGenerator::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigLoader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(ConfigYamlWriter::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DeclaredHeadingReader::class)]
#[UsesClass(DocGuardAnalysisRunner::class)]
#[UsesClass(DocGuardAnalyzer::class)]
#[UsesClass(DocGuardCliArgumentParser::class)]
#[UsesClass(DocGuardConfig::class)]
#[UsesClass(DocGuardConfigPathResolver::class)]
#[UsesClass(DocGuardException::class)]
#[UsesClass(DocGuardGenerateRunner::class)]
#[UsesClass(DocGuardHelpText::class)]
#[UsesClass(DocGuardOutputWriter::class)]
#[UsesClass(DocGuardPathResolver::class)]
#[UsesClass(DocGuardReporterOverride::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(DocumentConfigReader::class)]
#[UsesClass(DocumentListConfigReader::class)]
#[UsesClass(Fence::class)]
#[UsesClass(FenceMatcher::class)]
#[UsesClass(GenerationTargetCollector::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingHunkClassifier::class)]
#[UsesClass(HeadingParser::class)]
#[UsesClass(HeadingSequenceAligner::class)]
#[UsesClass(HeadingStructureComparator::class)]
#[UsesClass(HeadingTextNormalizer::class)]
#[UsesClass(HtmlBlockMatcher::class)]
#[UsesClass(JsonReporter::class)]
#[UsesClass(LineIndentation::class)]
#[UsesClass(LineScanner::class)]
#[UsesClass(MarkdownFileFinder::class)]
#[UsesClass(MarkdownFileReader::class)]
#[UsesClass(MarkdownLineSplitter::class)]
#[UsesClass(ParserState::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ReportConfigReader::class)]
#[UsesClass(Reporter::class)]
#[UsesClass(ReporterFactory::class)]
#[UsesClass(SetextUnderlineMatcher::class)]
#[UsesClass(TextReporter::class)]
#[UsesClass(UndeclaredDocumentInspector::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFactory::class)]
#[UsesClass(ViolationFieldComparator::class)]
#[UsesClass(ViolationSorter::class)]
final class ApplicationTest extends TestCase
{
    public function testRunReturnsZeroWhenStructureMatches(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-cli-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/README.md', "# Tool\n\n## Usage\n");
        file_put_contents($dir . '/doc-guard.yaml', "documents:\n  README.md:\n    headings: ['# Tool', '## Usage']\nscan: ['*.md']\n");
        $output = '';
        $app = new Application($dir, stdout: static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(0, $app->run(['doc-guard']));
        self::assertStringStartsWith('DOC_GUARD_PASSED', $output);
    }

    public function testRunReturnsOneWhenDocumentIsAdded(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-cli-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/README.md', "# Tool\n");
        file_put_contents($dir . '/DEVELOPMENT.md', "# Development\n");
        file_put_contents($dir . '/doc-guard.yaml', "documents:\n  README.md:\n    headings: ['# Tool']\nscan: ['*.md']\n");
        $output = '';
        $app = new Application($dir, stdout: static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(1, $app->run(['doc-guard', '--format', 'json']));
        self::assertStringContainsString('"rule": "undeclared_document"', $output);
    }

    public function testRunPrintsGeneratedConfig(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-cli-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/README.md', "# Tool\n");
        $output = '';
        $app = new Application($dir, stdout: static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(0, $app->run(['doc-guard', '--generate', 'README.md']));
        self::assertStringContainsString("      - '# Tool'\n", $output);
    }

    public function testRunPrintsHelpAndVersion(): void
    {
        $output = '';
        $app = new Application(sys_get_temp_dir(), stdout: static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(0, $app->run(['doc-guard', '--help']));
        self::assertStringContainsString('Usage:', $output);

        $output = '';

        self::assertSame(0, $app->run(['doc-guard', '-V']));
        self::assertSame("doc-guard 1.0.0\n", $output);
    }

    public function testRunReturnsTwoWhenConfigIsMissing(): void
    {
        $error = '';
        $dir = sys_get_temp_dir() . '/docguard-cli-' . uniqid('', true);
        mkdir($dir);
        $app = new Application($dir, stderr: static function (string $message) use (&$error): void {
            $error .= $message;
        });

        self::assertSame(2, $app->run(['doc-guard']));
        self::assertStringContainsString('DocGuard config not found: ' . $dir . '/doc-guard.yaml', $error);
    }

    public function testRunRejectsUnknownOption(): void
    {
        $error = '';
        $app = new Application(sys_get_temp_dir(), stderr: static function (string $message) use (&$error): void {
            $error .= $message;
        });

        self::assertSame(2, $app->run(['doc-guard', '--unknown']));
        self::assertSame("DocGuard error: Unknown option: --unknown\n", $error);
    }
}
