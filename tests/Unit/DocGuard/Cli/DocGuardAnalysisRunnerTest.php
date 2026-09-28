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
use Toolkit\DocGuard\Cli\DocGuardAnalysisRunner;
use Toolkit\DocGuard\Cli\DocGuardConfigPathResolver;
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
 * @covers \Toolkit\DocGuard\Cli\DocGuardAnalysisRunner
 * @uses \Toolkit\DocGuard\Reporting\AiReportGuidance
 * @uses \Toolkit\DocGuard\Reporting\AiReportSummary
 * @uses \Toolkit\DocGuard\Reporting\AiReporter
 * @uses \Toolkit\DocGuard\Reporting\AiViolationAction
 * @uses \Toolkit\DocGuard\Reporting\AiViolationFormatter
 * @uses \Toolkit\DocGuard\Analysis\AnalysisResult
 * @uses \Toolkit\DocGuard\Markdown\AtxHeadingMatcher
 * @uses \Toolkit\DocGuard\Markdown\BlockLineScanner
 * @uses \Toolkit\DocGuard\Markdown\BlockMarkerMatcher
 * @uses \Toolkit\DocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\DocGuard\Config\ConfigLoader
 * @uses \Toolkit\DocGuard\Config\ConfigStringListReader
 * @uses \Toolkit\DocGuard\Config\DeclaredHeading
 * @uses \Toolkit\DocGuard\Config\DeclaredHeadingReader
 * @uses \Toolkit\DocGuard\Analysis\DocGuardAnalyzer
 * @uses \Toolkit\DocGuard\Config\DocGuardConfig
 * @uses \Toolkit\DocGuard\Cli\DocGuardConfigPathResolver
 * @uses \Toolkit\DocGuard\DocGuardException
 * @uses \Toolkit\DocGuard\Cli\DocGuardOutputWriter
 * @uses \Toolkit\DocGuard\Filesystem\DocGuardPathResolver
 * @uses \Toolkit\DocGuard\Cli\DocGuardReporterOverride
 * @uses \Toolkit\DocGuard\Config\DocumentConfig
 * @uses \Toolkit\DocGuard\Config\DocumentConfigReader
 * @uses \Toolkit\DocGuard\Config\DocumentListConfigReader
 * @uses \Toolkit\DocGuard\Markdown\Fence
 * @uses \Toolkit\DocGuard\Markdown\FenceMatcher
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
#[CoversClass(DocGuardAnalysisRunner::class)]
#[UsesClass(AiReportGuidance::class)]
#[UsesClass(AiReportSummary::class)]
#[UsesClass(AiReporter::class)]
#[UsesClass(AiViolationAction::class)]
#[UsesClass(AiViolationFormatter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(BlockLineScanner::class)]
#[UsesClass(BlockMarkerMatcher::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigLoader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DeclaredHeadingReader::class)]
#[UsesClass(DocGuardAnalyzer::class)]
#[UsesClass(DocGuardConfig::class)]
#[UsesClass(DocGuardConfigPathResolver::class)]
#[UsesClass(DocGuardException::class)]
#[UsesClass(DocGuardOutputWriter::class)]
#[UsesClass(DocGuardPathResolver::class)]
#[UsesClass(DocGuardReporterOverride::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(DocumentConfigReader::class)]
#[UsesClass(DocumentListConfigReader::class)]
#[UsesClass(Fence::class)]
#[UsesClass(FenceMatcher::class)]
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
final class DocGuardAnalysisRunnerTest extends TestCase
{
    public function testRunReturnsZeroForDeclaredStructure(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-runner-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/README.md', "# Tool\n\nAny content.\n");
        file_put_contents($dir . '/doc-guard.yaml', "documents:\n  README.md:\n    headings: ['# Tool']\n");
        $output = '';
        $writer = new DocGuardOutputWriter(static function (string $message) use (&$output): void {
            $output .= $message;
        });

        $runner = new DocGuardAnalysisRunner($dir, new ConfigLoader(), new DocGuardAnalyzer(), new ReporterFactory(), $writer);

        self::assertSame(0, $runner->run('doc-guard.yaml', 'text'));
        self::assertStringStartsWith('DocGuard passed.', $output);
    }

    public function testRunReturnsOneForAddedSection(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-runner-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/README.md', "# Tool\n\n## Development\n");
        file_put_contents($dir . '/doc-guard.yaml', "documents:\n  README.md:\n    headings: ['# Tool']\n");
        $output = '';
        $writer = new DocGuardOutputWriter(static function (string $message) use (&$output): void {
            $output .= $message;
        });

        $runner = new DocGuardAnalysisRunner($dir, new ConfigLoader(), new DocGuardAnalyzer(), new ReporterFactory(), $writer);

        self::assertSame(1, $runner->run('doc-guard.yaml', null));
        self::assertStringContainsString('1. README.md:3 [unexpected_heading]', $output);
    }

    public function testRunReturnsTwoForUnknownReporter(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-runner-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/README.md', "# Tool\n");
        file_put_contents($dir . '/doc-guard.yaml', "documents:\n  README.md:\n    headings: ['# Tool']\n");
        $error = '';
        $writer = new DocGuardOutputWriter(null, static function (string $message) use (&$error): void {
            $error .= $message;
        });

        $runner = new DocGuardAnalysisRunner($dir, new ConfigLoader(), new DocGuardAnalyzer(), new ReporterFactory(), $writer);

        self::assertSame(2, $runner->run($dir . '/doc-guard.yaml', 'xml'));
        self::assertSame("DocGuard error: Unknown DocGuard reporter: xml. Use one of: ai, text, json.\n", $error);
    }
}
