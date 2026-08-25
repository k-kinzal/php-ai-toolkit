<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Analysis\AnalysisResult;
use Toolkit\ScopeGuard\Analysis\Declaration\ClassLikeKind;
use Toolkit\ScopeGuard\Analysis\Declaration\Declaration;
use Toolkit\ScopeGuard\Analysis\Declaration\DeclarationCollector;
use Toolkit\ScopeGuard\Analysis\Declaration\DeclarationIndex;
use Toolkit\ScopeGuard\Analysis\Parse\FileNamespaces;
use Toolkit\ScopeGuard\Analysis\Parse\NodeWalker;
use Toolkit\ScopeGuard\Analysis\Parse\PhpParserBridge;
use Toolkit\ScopeGuard\Analysis\Parse\SourceFileParser;
use Toolkit\ScopeGuard\Analysis\ProjectScan;
use Toolkit\ScopeGuard\Analysis\ProjectScanner;
use Toolkit\ScopeGuard\Analysis\Reference\Reference;
use Toolkit\ScopeGuard\Analysis\Reference\ReferenceCollector;
use Toolkit\ScopeGuard\Analysis\Reference\TypeNameReader;
use Toolkit\ScopeGuard\Analysis\Scope\ExemptNamespaces;
use Toolkit\ScopeGuard\Analysis\Scope\NamespaceLineage;
use Toolkit\ScopeGuard\Analysis\Scope\ScopeProblemReader;
use Toolkit\ScopeGuard\Analysis\Scope\VisibilityScope;
use Toolkit\ScopeGuard\Analysis\Scope\VisibilityScopeResolver;
use Toolkit\ScopeGuard\Analysis\Scope\VisibilityTagParser;
use Toolkit\ScopeGuard\Analysis\ScopeChecker;
use Toolkit\ScopeGuard\Analysis\ScopeGuardAnalyzer;
use Toolkit\ScopeGuard\Analysis\ScopeViolationBuilder;
use Toolkit\ScopeGuard\Analysis\Violation;
use Toolkit\ScopeGuard\Cli\Application;
use Toolkit\ScopeGuard\Cli\ScopeGuardAnalysisRunner;
use Toolkit\ScopeGuard\Cli\ScopeGuardCliArgumentParser;
use Toolkit\ScopeGuard\Cli\ScopeGuardConfigPathResolver;
use Toolkit\ScopeGuard\Cli\ScopeGuardHelpText;
use Toolkit\ScopeGuard\Cli\ScopeGuardOutputWriter;
use Toolkit\ScopeGuard\Cli\ScopeGuardReporterOverride;
use Toolkit\ScopeGuard\Config\ConfigLoader;
use Toolkit\ScopeGuard\Config\ConfigScalarReader;
use Toolkit\ScopeGuard\Config\ConfigStringListReader;
use Toolkit\ScopeGuard\Config\ReportConfig;
use Toolkit\ScopeGuard\Config\ReportConfigReader;
use Toolkit\ScopeGuard\Config\ScopeGuardConfig;
use Toolkit\ScopeGuard\Filesystem\PhpFileFinder;
use Toolkit\ScopeGuard\Filesystem\PhpFileInclusionPolicy;
use Toolkit\ScopeGuard\Filesystem\PhpPathFileCollector;
use Toolkit\ScopeGuard\Filesystem\ScopeGuardPathResolver;
use Toolkit\ScopeGuard\Reporting\AiReporter;
use Toolkit\ScopeGuard\Reporting\AiReportGuidance;
use Toolkit\ScopeGuard\Reporting\AiReportSummary;
use Toolkit\ScopeGuard\Reporting\AiViolationAction;
use Toolkit\ScopeGuard\Reporting\AiViolationFormatter;
use Toolkit\ScopeGuard\Reporting\ReporterFactory;
use Toolkit\ScopeGuard\Reporting\TextReporter;
use Toolkit\ScopeGuard\Reporting\ViolationFieldComparator;
use Toolkit\ScopeGuard\Reporting\ViolationSorter;
use Toolkit\ScopeGuard\ScopeGuardException;

/**
 * @covers \Toolkit\ScopeGuard\Cli\Application
 * @uses \Toolkit\ScopeGuard\Cli\ScopeGuardAnalysisRunner
 * @uses \Toolkit\ScopeGuard\Reporting\AiReporter
 * @uses \Toolkit\ScopeGuard\Reporting\AiReportGuidance
 * @uses \Toolkit\ScopeGuard\Reporting\AiReportSummary
 * @uses \Toolkit\ScopeGuard\Reporting\AiViolationAction
 * @uses \Toolkit\ScopeGuard\Reporting\AiViolationFormatter
 * @uses \Toolkit\ScopeGuard\Analysis\AnalysisResult
 * @uses \Toolkit\ScopeGuard\Analysis\Declaration\ClassLikeKind
 * @uses \Toolkit\ScopeGuard\Config\ConfigLoader
 * @uses \Toolkit\ScopeGuard\Config\ConfigScalarReader
 * @uses \Toolkit\ScopeGuard\Config\ConfigStringListReader
 * @uses \Toolkit\ScopeGuard\Analysis\Declaration\Declaration
 * @uses \Toolkit\ScopeGuard\Analysis\Declaration\DeclarationCollector
 * @uses \Toolkit\ScopeGuard\Analysis\Declaration\DeclarationIndex
 * @uses \Toolkit\ScopeGuard\Analysis\Scope\ExemptNamespaces
 * @uses \Toolkit\ScopeGuard\Analysis\Parse\FileNamespaces
 * @uses \Toolkit\ScopeGuard\Analysis\Scope\NamespaceLineage
 * @uses \Toolkit\ScopeGuard\Analysis\Parse\NodeWalker
 * @uses \Toolkit\ScopeGuard\Filesystem\PhpFileFinder
 * @uses \Toolkit\ScopeGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \Toolkit\ScopeGuard\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\ScopeGuard\Filesystem\PhpPathFileCollector
 * @uses \Toolkit\ScopeGuard\Analysis\ProjectScan
 * @uses \Toolkit\ScopeGuard\Analysis\ProjectScanner
 * @uses \Toolkit\ScopeGuard\Analysis\Reference\Reference
 * @uses \Toolkit\ScopeGuard\Analysis\Reference\ReferenceCollector
 * @uses \Toolkit\ScopeGuard\Config\ReportConfig
 * @uses \Toolkit\ScopeGuard\Config\ReportConfigReader
 * @uses \Toolkit\ScopeGuard\Reporting\ReporterFactory
 * @uses \Toolkit\ScopeGuard\Analysis\ScopeChecker
 * @uses \Toolkit\ScopeGuard\Analysis\ScopeGuardAnalyzer
 * @uses \Toolkit\ScopeGuard\Config\ScopeGuardConfig
 * @uses \Toolkit\ScopeGuard\Cli\ScopeGuardCliArgumentParser
 * @uses \Toolkit\ScopeGuard\Cli\ScopeGuardConfigPathResolver
 * @uses \Toolkit\ScopeGuard\ScopeGuardException
 * @uses \Toolkit\ScopeGuard\Cli\ScopeGuardHelpText
 * @uses \Toolkit\ScopeGuard\Cli\ScopeGuardOutputWriter
 * @uses \Toolkit\ScopeGuard\Filesystem\ScopeGuardPathResolver
 * @uses \Toolkit\ScopeGuard\Cli\ScopeGuardReporterOverride
 * @uses \Toolkit\ScopeGuard\Analysis\Scope\ScopeProblemReader
 * @uses \Toolkit\ScopeGuard\Analysis\ScopeViolationBuilder
 * @uses \Toolkit\ScopeGuard\Analysis\Parse\SourceFileParser
 * @uses \Toolkit\ScopeGuard\Reporting\TextReporter
 * @uses \Toolkit\ScopeGuard\Analysis\Reference\TypeNameReader
 * @uses \Toolkit\ScopeGuard\Analysis\Scope\VisibilityScope
 * @uses \Toolkit\ScopeGuard\Analysis\Scope\VisibilityScopeResolver
 * @uses \Toolkit\ScopeGuard\Analysis\Scope\VisibilityTagParser
 * @uses \Toolkit\ScopeGuard\Analysis\Violation
 * @uses \Toolkit\ScopeGuard\Reporting\ViolationFieldComparator
 * @uses \Toolkit\ScopeGuard\Reporting\ViolationSorter
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
