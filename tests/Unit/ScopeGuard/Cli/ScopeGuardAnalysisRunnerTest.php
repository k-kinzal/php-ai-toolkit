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
use PhpAiToolkit\ScopeGuard\Cli\ScopeGuardAnalysisRunner;
use PhpAiToolkit\ScopeGuard\Cli\ScopeGuardConfigPathResolver;
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

#[CoversClass(ScopeGuardAnalysisRunner::class)]
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
#[UsesClass(ScopeGuardConfigPathResolver::class)]
#[UsesClass(ScopeGuardException::class)]
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
final class ScopeGuardAnalysisRunnerTest extends TestCase
{
    public function testRunReportsViolationsWithExitCodeOne(): void
    {
        $output = '';
        $writer = new ScopeGuardOutputWriter(static function (string $message) use (&$output): void {
            $output .= $message;
        });
        $runner = new ScopeGuardAnalysisRunner(
            __DIR__ . '/../../../Fixture/ScopeGuard/project',
            new ConfigLoader(),
            new ScopeGuardAnalyzer(),
            new ReporterFactory(),
            $writer,
        );

        self::assertSame(1, $runner->run('scope.yaml', null));
    }

    public function testRunWritesTheSelectedReport(): void
    {
        $output = '';
        $writer = new ScopeGuardOutputWriter(static function (string $message) use (&$output): void {
            $output .= $message;
        });
        $runner = new ScopeGuardAnalysisRunner(
            __DIR__ . '/../../../Fixture/ScopeGuard/project',
            new ConfigLoader(),
            new ScopeGuardAnalyzer(),
            new ReporterFactory(),
            $writer,
        );
        $runner->run('scope.yaml', 'text');

        self::assertStringStartsWith('ScopeGuard found', $output);
    }

    public function testRunPassesWhenEveryReferenceIsExempt(): void
    {
        $writer = new ScopeGuardOutputWriter(static function (string $message): void {
        });
        $runner = new ScopeGuardAnalysisRunner(
            __DIR__ . '/../../../Fixture/ScopeGuard/exempt-project',
            new ConfigLoader(),
            new ScopeGuardAnalyzer(),
            new ReporterFactory(),
            $writer,
        );

        self::assertSame(0, $runner->run('scope.yaml', null));
    }

    public function testRunReportsAConfigErrorWithExitCodeTwo(): void
    {
        $errors = '';
        $writer = new ScopeGuardOutputWriter(null, static function (string $message) use (&$errors): void {
            $errors .= $message;
        });
        $runner = new ScopeGuardAnalysisRunner(
            __DIR__ . '/../../../Fixture/ScopeGuard/project',
            new ConfigLoader(),
            new ScopeGuardAnalyzer(),
            new ReporterFactory(),
            $writer,
        );

        self::assertSame(2, $runner->run('absent.yaml', null));
    }

    public function testRunExplainsTheConfigError(): void
    {
        $errors = '';
        $writer = new ScopeGuardOutputWriter(null, static function (string $message) use (&$errors): void {
            $errors .= $message;
        });
        $runner = new ScopeGuardAnalysisRunner(
            __DIR__ . '/../../../Fixture/ScopeGuard/project',
            new ConfigLoader(),
            new ScopeGuardAnalyzer(),
            new ReporterFactory(),
            $writer,
        );
        $runner->run('absent.yaml', null);

        self::assertStringStartsWith('ScopeGuard error: ScopeGuard config not found', $errors);
    }
}
