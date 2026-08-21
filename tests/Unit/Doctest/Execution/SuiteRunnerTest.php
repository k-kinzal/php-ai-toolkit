<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Execution;

use PhpAiToolkit\Doctest\Analysis\AssertionLine;
use PhpAiToolkit\Doctest\Analysis\AssertionScanner;
use PhpAiToolkit\Doctest\Analysis\DocExample;
use PhpAiToolkit\Doctest\Analysis\DoctestExtractor;
use PhpAiToolkit\Doctest\Analysis\Example;
use PhpAiToolkit\Doctest\Analysis\ExampleCollector;
use PhpAiToolkit\Doctest\Analysis\ExampleFilter;
use PhpAiToolkit\Doctest\Analysis\ImportPreamble;
use PhpAiToolkit\Doctest\Analysis\PhpParserBridge;
use PhpAiToolkit\Doctest\Analysis\SourceScanner;
use PhpAiToolkit\Doctest\Analysis\Target;
use PhpAiToolkit\Doctest\Config\ConfigLoader;
use PhpAiToolkit\Doctest\Config\ConfigScalarReader;
use PhpAiToolkit\Doctest\Config\ConfigStringListReader;
use PhpAiToolkit\Doctest\Config\DoctestConfig;
use PhpAiToolkit\Doctest\Config\ReportConfig;
use PhpAiToolkit\Doctest\Config\ReportConfigReader;
use PhpAiToolkit\Doctest\Execution\DiagnosticLog;
use PhpAiToolkit\Doctest\Execution\ExampleRunner;
use PhpAiToolkit\Doctest\Execution\ExceptionMatcher;
use PhpAiToolkit\Doctest\Execution\ExecutionContext;
use PhpAiToolkit\Doctest\Execution\ExpressionEvaluator;
use PhpAiToolkit\Doctest\Execution\ReturnPolicy;
use PhpAiToolkit\Doctest\Execution\RunFailure;
use PhpAiToolkit\Doctest\Execution\RunResult;
use PhpAiToolkit\Doctest\Execution\SourceLoader;
use PhpAiToolkit\Doctest\Execution\SourceSyntax;
use PhpAiToolkit\Doctest\Execution\Statement;
use PhpAiToolkit\Doctest\Execution\StatementBuilder;
use PhpAiToolkit\Doctest\Execution\StatementRunner;
use PhpAiToolkit\Doctest\Execution\SuiteResult;
use PhpAiToolkit\Doctest\Execution\SuiteRunner;
use PhpAiToolkit\Doctest\Execution\ValueFormatter;
use PhpAiToolkit\Doctest\Filesystem\DoctestPathResolver;
use PhpAiToolkit\Doctest\Filesystem\PhpFileFinder;
use PhpAiToolkit\Doctest\Filesystem\PhpFileInclusionPolicy;
use PhpAiToolkit\Doctest\Filesystem\PhpPathFileCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SuiteRunner::class)]
#[UsesClass(SuiteResult::class)]
#[UsesClass(RunResult::class)]
#[UsesClass(RunFailure::class)]
#[UsesClass(Example::class)]
#[UsesClass(Target::class)]
#[UsesClass(DocExample::class)]
#[UsesClass(ExampleCollector::class)]
#[UsesClass(ExampleFilter::class)]
#[UsesClass(ExampleRunner::class)]
#[UsesClass(SourceScanner::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(ImportPreamble::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PhpFileFinder::class)]
#[UsesClass(PhpPathFileCollector::class)]
#[UsesClass(PhpFileInclusionPolicy::class)]
#[UsesClass(DoctestPathResolver::class)]
#[UsesClass(ConfigLoader::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(ReportConfigReader::class)]
#[UsesClass(DoctestConfig::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(StatementBuilder::class)]
#[UsesClass(StatementRunner::class)]
#[UsesClass(SourceLoader::class)]
#[UsesClass(Statement::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssertionLine::class)]
#[UsesClass(SourceSyntax::class)]
#[UsesClass(ExpressionEvaluator::class)]
#[UsesClass(ExceptionMatcher::class)]
#[UsesClass(ValueFormatter::class)]
#[UsesClass(ExecutionContext::class)]
#[UsesClass(ReturnPolicy::class)]
#[UsesClass(DiagnosticLog::class)]
#[Medium]
final class SuiteRunnerTest extends TestCase
{
    public function testCollectListsTheSelectedExamplesWithoutRunningThem(): void
    {
        $config = (new ConfigLoader())->load(__DIR__ . '/../../../Fixture/Doctest/project/doctest.yaml');

        $examples = (new SuiteRunner())->collect($config);

        self::assertCount(6, $examples);
        self::assertSame('Tests\Fixture\Doctest\Project\Calculator#1', $examples[0]->id());
    }

    public function testCollectAppliesTheFilter(): void
    {
        $config = (new ConfigLoader())->load(__DIR__ . '/../../../Fixture/Doctest/project/doctest.yaml');

        $examples = (new SuiteRunner())->collect($config, 'divide');

        self::assertCount(1, $examples);
        self::assertSame('Tests\Fixture\Doctest\Project\Calculator::divide()#1', $examples[0]->id());
    }

    public function testRunExecutesEverySelectedExample(): void
    {
        $config = (new ConfigLoader())->load(__DIR__ . '/../../../Fixture/Doctest/project/doctest.yaml');

        $result = (new SuiteRunner())->run($config);

        self::assertFalse($result->hasFailures());
        self::assertSame(1, $result->fileCount);
        self::assertSame(6, $result->total());
        self::assertSame(5, $result->passedCount());
        self::assertSame(1, $result->skippedCount());
    }

    public function testRunReportsAnExampleThatDoesNotHold(): void
    {
        $config = (new ConfigLoader())->load(__DIR__ . '/../../../Fixture/Doctest/failing/doctest.yaml');

        $result = (new SuiteRunner())->run($config);

        self::assertTrue($result->hasFailures());
        self::assertSame(1, $result->failedCount());
    }

    public function testCollectFromReadsTheExamplesOfEveryGivenFile(): void
    {
        $path = realpath(__DIR__ . '/../../../Fixture/Doctest/project/src/Calculator.php');

        $examples = (new SuiteRunner())->collectFrom([(string) $path => 'src/Calculator.php']);

        self::assertCount(6, $examples);
        self::assertSame('src/Calculator.php', $examples[0]->target->reportPath());
    }
}
