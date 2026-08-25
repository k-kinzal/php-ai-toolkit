<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Cli;

use PhpAiToolkit\ScopeGuard\Analysis\AnalysisResult;
use PhpAiToolkit\ScopeGuard\Analysis\Declaration\ClassLikeKind;
use PhpAiToolkit\ScopeGuard\Analysis\Declaration\Declaration;
use PhpAiToolkit\ScopeGuard\Analysis\Declaration\DeclarationCollector;
use PhpAiToolkit\ScopeGuard\Analysis\Declaration\DeclarationIndex;
use PhpAiToolkit\ScopeGuard\Analysis\Parse\FileNamespaces;
use PhpAiToolkit\ScopeGuard\Analysis\Parse\NodeWalker;
use PhpAiToolkit\ScopeGuard\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\ScopeGuard\Analysis\Parse\SourceFileParser;
use PhpAiToolkit\ScopeGuard\Analysis\ProjectScan;
use PhpAiToolkit\ScopeGuard\Analysis\ProjectScanner;
use PhpAiToolkit\ScopeGuard\Analysis\Reference\Reference;
use PhpAiToolkit\ScopeGuard\Analysis\Reference\ReferenceCollector;
use PhpAiToolkit\ScopeGuard\Analysis\Reference\TypeNameReader;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\ExemptNamespaces;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\NamespaceLineage;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\ScopeProblemReader;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScope;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScopeResolver;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityTagParser;
use PhpAiToolkit\ScopeGuard\Analysis\ScopeChecker;
use PhpAiToolkit\ScopeGuard\Analysis\ScopeGuardAnalyzer;
use PhpAiToolkit\ScopeGuard\Analysis\ScopeViolationBuilder;
use PhpAiToolkit\ScopeGuard\Analysis\Violation;
use PhpAiToolkit\ScopeGuard\Cli\Application;
use PhpAiToolkit\ScopeGuard\Cli\ScopeGuardAnalysisRunner;
use PhpAiToolkit\ScopeGuard\Cli\ScopeGuardCliArgumentParser;
use PhpAiToolkit\ScopeGuard\Cli\ScopeGuardConfigPathResolver;
use PhpAiToolkit\ScopeGuard\Cli\ScopeGuardHelpText;
use PhpAiToolkit\ScopeGuard\Cli\ScopeGuardOutputWriter;
use PhpAiToolkit\ScopeGuard\Cli\ScopeGuardReporterOverride;
use PhpAiToolkit\ScopeGuard\Config\ConfigLoader;
use PhpAiToolkit\ScopeGuard\Config\ConfigScalarReader;
use PhpAiToolkit\ScopeGuard\Config\ConfigStringListReader;
use PhpAiToolkit\ScopeGuard\Config\ReportConfig;
use PhpAiToolkit\ScopeGuard\Config\ReportConfigReader;
use PhpAiToolkit\ScopeGuard\Config\ScopeGuardConfig;
use PhpAiToolkit\ScopeGuard\Filesystem\PhpFileFinder;
use PhpAiToolkit\ScopeGuard\Filesystem\PhpFileInclusionPolicy;
use PhpAiToolkit\ScopeGuard\Filesystem\PhpPathFileCollector;
use PhpAiToolkit\ScopeGuard\Filesystem\ScopeGuardPathResolver;
use PhpAiToolkit\ScopeGuard\Reporting\AiReporter;
use PhpAiToolkit\ScopeGuard\Reporting\AiReportGuidance;
use PhpAiToolkit\ScopeGuard\Reporting\AiReportSummary;
use PhpAiToolkit\ScopeGuard\Reporting\AiViolationAction;
use PhpAiToolkit\ScopeGuard\Reporting\AiViolationFormatter;
use PhpAiToolkit\ScopeGuard\Reporting\ReporterFactory;
use PhpAiToolkit\ScopeGuard\Reporting\TextReporter;
use PhpAiToolkit\ScopeGuard\Reporting\ViolationFieldComparator;
use PhpAiToolkit\ScopeGuard\Reporting\ViolationSorter;
use PhpAiToolkit\ScopeGuard\ScopeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\ScopeGuard\Cli\Application
 * @uses \PhpAiToolkit\ScopeGuard\Cli\ScopeGuardAnalysisRunner
 * @uses \PhpAiToolkit\ScopeGuard\Reporting\AiReporter
 * @uses \PhpAiToolkit\ScopeGuard\Reporting\AiReportGuidance
 * @uses \PhpAiToolkit\ScopeGuard\Reporting\AiReportSummary
 * @uses \PhpAiToolkit\ScopeGuard\Reporting\AiViolationAction
 * @uses \PhpAiToolkit\ScopeGuard\Reporting\AiViolationFormatter
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\AnalysisResult
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Declaration\ClassLikeKind
 * @uses \PhpAiToolkit\ScopeGuard\Config\ConfigLoader
 * @uses \PhpAiToolkit\ScopeGuard\Config\ConfigScalarReader
 * @uses \PhpAiToolkit\ScopeGuard\Config\ConfigStringListReader
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Declaration\Declaration
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Declaration\DeclarationCollector
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Declaration\DeclarationIndex
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\ExemptNamespaces
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Parse\FileNamespaces
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\NamespaceLineage
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Parse\NodeWalker
 * @uses \PhpAiToolkit\ScopeGuard\Filesystem\PhpFileFinder
 * @uses \PhpAiToolkit\ScopeGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Parse\PhpParserBridge
 * @uses \PhpAiToolkit\ScopeGuard\Filesystem\PhpPathFileCollector
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\ProjectScan
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\ProjectScanner
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Reference\Reference
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Reference\ReferenceCollector
 * @uses \PhpAiToolkit\ScopeGuard\Config\ReportConfig
 * @uses \PhpAiToolkit\ScopeGuard\Config\ReportConfigReader
 * @uses \PhpAiToolkit\ScopeGuard\Reporting\ReporterFactory
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\ScopeChecker
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\ScopeGuardAnalyzer
 * @uses \PhpAiToolkit\ScopeGuard\Config\ScopeGuardConfig
 * @uses \PhpAiToolkit\ScopeGuard\Cli\ScopeGuardCliArgumentParser
 * @uses \PhpAiToolkit\ScopeGuard\Cli\ScopeGuardConfigPathResolver
 * @uses \PhpAiToolkit\ScopeGuard\ScopeGuardException
 * @uses \PhpAiToolkit\ScopeGuard\Cli\ScopeGuardHelpText
 * @uses \PhpAiToolkit\ScopeGuard\Cli\ScopeGuardOutputWriter
 * @uses \PhpAiToolkit\ScopeGuard\Filesystem\ScopeGuardPathResolver
 * @uses \PhpAiToolkit\ScopeGuard\Cli\ScopeGuardReporterOverride
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\ScopeProblemReader
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\ScopeViolationBuilder
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Parse\SourceFileParser
 * @uses \PhpAiToolkit\ScopeGuard\Reporting\TextReporter
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Reference\TypeNameReader
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScope
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScopeResolver
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityTagParser
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Violation
 * @uses \PhpAiToolkit\ScopeGuard\Reporting\ViolationFieldComparator
 * @uses \PhpAiToolkit\ScopeGuard\Reporting\ViolationSorter
 */
#[CoversClass(Application::class)]
#[UsesClass(ScopeGuardAnalysisRunner::class)]
#[UsesClass(AiReporter::class)]
#[UsesClass(AiReportGuidance::class)]
#[UsesClass(AiReportSummary::class)]
#[UsesClass(AiViolationAction::class)]
#[UsesClass(AiViolationFormatter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(ClassLikeKind::class)]
#[UsesClass(ConfigLoader::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(Declaration::class)]
#[UsesClass(DeclarationCollector::class)]
#[UsesClass(DeclarationIndex::class)]
#[UsesClass(ExemptNamespaces::class)]
#[UsesClass(FileNamespaces::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(NodeWalker::class)]
#[UsesClass(PhpFileFinder::class)]
#[UsesClass(PhpFileInclusionPolicy::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PhpPathFileCollector::class)]
#[UsesClass(ProjectScan::class)]
#[UsesClass(ProjectScanner::class)]
#[UsesClass(Reference::class)]
#[UsesClass(ReferenceCollector::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ReportConfigReader::class)]
#[UsesClass(ReporterFactory::class)]
#[UsesClass(ScopeChecker::class)]
#[UsesClass(ScopeGuardAnalyzer::class)]
#[UsesClass(ScopeGuardConfig::class)]
#[UsesClass(ScopeGuardCliArgumentParser::class)]
#[UsesClass(ScopeGuardConfigPathResolver::class)]
#[UsesClass(ScopeGuardException::class)]
#[UsesClass(ScopeGuardHelpText::class)]
#[UsesClass(ScopeGuardOutputWriter::class)]
#[UsesClass(ScopeGuardPathResolver::class)]
#[UsesClass(ScopeGuardReporterOverride::class)]
#[UsesClass(ScopeProblemReader::class)]
#[UsesClass(ScopeViolationBuilder::class)]
#[UsesClass(SourceFileParser::class)]
#[UsesClass(TextReporter::class)]
#[UsesClass(TypeNameReader::class)]
#[UsesClass(VisibilityScope::class)]
#[UsesClass(VisibilityScopeResolver::class)]
#[UsesClass(VisibilityTagParser::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFieldComparator::class)]
#[UsesClass(ViolationSorter::class)]
#[Medium]
final class ApplicationTest extends TestCase
{
    public function testRunReportsViolationsWithExitCodeOne(): void
    {
        $application = new Application(
            __DIR__ . '/../../../Fixture/ScopeGuard/project',
            null,
            null,
            null,
            static function (string $message): void {
            },
        );

        self::assertSame(1, $application->run(['scope-guard', '--config=scope.yaml']));
    }

    public function testRunPassesWhenEveryReferenceIsInScope(): void
    {
        $application = new Application(
            __DIR__ . '/../../../Fixture/ScopeGuard/exempt-project',
            null,
            null,
            null,
            static function (string $message): void {
            },
        );

        self::assertSame(0, $application->run(['scope-guard', '--config=scope.yaml']));
    }

    public function testRunPrintsTheHelpText(): void
    {
        $output = '';
        $application = new Application('/project', null, null, null, static function (string $message) use (&$output): void {
            $output .= $message;
        });
        $application->run(['scope-guard', '--help']);

        self::assertStringStartsWith('scope-guard checks', $output);
    }

    public function testRunPrintsTheVersion(): void
    {
        $output = '';
        $application = new Application('/project', null, null, null, static function (string $message) use (&$output): void {
            $output .= $message;
        });
        $application->run(['scope-guard', '--version']);

        self::assertSame("scope-guard 1.0.0\n", $output);
    }

    public function testRunReportsAnUnknownOptionWithExitCodeTwo(): void
    {
        $application = new Application('/project', null, null, null, null, static function (string $message): void {
        });

        self::assertSame(2, $application->run(['scope-guard', '--strict']));
    }

    public function testRunExplainsAnUnknownOption(): void
    {
        $errors = '';
        $application = new Application('/project', null, null, null, null, static function (string $message) use (&$errors): void {
            $errors .= $message;
        });
        $application->run(['scope-guard', '--strict']);

        self::assertSame("ScopeGuard error: Unknown option: --strict\n", $errors);
    }
}
