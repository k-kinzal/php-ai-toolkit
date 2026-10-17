<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Php;

use Guard\Config\Configuration;
use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Input\DirectoryListing;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\FileSet;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Selection;
use Guard\Input\StructuredFile;
use Guard\Policy\Context;
use Guard\Policy\Definition\LimitConfig;
use Guard\Policy\Diagnostic\MetricViolation;
use Guard\Policy\FileChange;
use Guard\Policy\Limit\ClassLikeMetricLimit;
use Guard\Policy\Limit\ClassLikeMetricViolationBuilder;
use Guard\Policy\Limit\FileMetricViolationBuilder;
use Guard\Policy\Limit\FunctionComplexityViolationBuilder;
use Guard\Policy\Limit\FunctionLineViolationBuilder;
use Guard\Policy\Limit\FunctionMetricViolationBuilder;
use Guard\Policy\Limit\MetricLimitInspector;
use Guard\Policy\Plan;
use Guard\Policy\PolicyBinding;
use Guard\Structure\Php\ClassLikeMetric\ClassLikeDeclarationReader;
use Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric;
use Guard\Structure\Php\ClassLikeMetric\ClassLikeMetricParser;
use Guard\Structure\Php\Complexity\CyclomaticComplexityCalculator;
use Guard\Structure\Php\Complexity\CyclomaticComplexityState;
use Guard\Structure\Php\Complexity\CyclomaticDecisionWeight;
use Guard\Structure\Php\FileMetric\FileMetric;
use Guard\Structure\Php\FunctionMetric\ArrowExpressionBoundary;
use Guard\Structure\Php\FunctionMetric\ArrowFunctionMetricReader;
use Guard\Structure\Php\FunctionMetric\BlockFunctionMetricReader;
use Guard\Structure\Php\FunctionMetric\FunctionBodyLocator;
use Guard\Structure\Php\FunctionMetric\FunctionLineParser;
use Guard\Structure\Php\FunctionMetric\FunctionMetric;
use Guard\Structure\Php\FunctionMetric\FunctionMetricComplexityAssigner;
use Guard\Structure\Php\FunctionMetric\FunctionMetricParser;
use Guard\Structure\Php\FunctionMetric\FunctionNameReader;
use Guard\Structure\Php\FunctionMetric\FunctionScanState;
use Guard\Structure\Php\FunctionMetric\NestedFunctionMetricRange;
use Guard\Structure\Php\MetricParser;
use Guard\Structure\Php\SourceMetrics;
use Guard\Structure\Php\Token\ClassLikeTokenMatcher;
use Guard\Structure\Php\Token\CodeTokenLineResolver;
use Guard\Structure\Php\Token\PhpTokenNavigator;
use Guard\Structure\Php\Token\TokenLineCounter;
use Guard\Structure\Php\TokenParser;
use Guard\Structure\Php\Tokens;
use Guard\Structure\Source;
use JsonException;
use Nette\Neon\Exception;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Php\MetricParser
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Policy\Definition\LimitConfig
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Policy\Limit\ClassLikeMetricLimit
 * @uses \Guard\Policy\Limit\ClassLikeMetricViolationBuilder
 * @uses \Guard\Policy\Limit\FileMetricViolationBuilder
 * @uses \Guard\Policy\Limit\FunctionComplexityViolationBuilder
 * @uses \Guard\Policy\Limit\FunctionLineViolationBuilder
 * @uses \Guard\Policy\Limit\FunctionMetricViolationBuilder
 * @uses \Guard\Policy\Limit\MetricLimitInspector
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Policy\Diagnostic\MetricViolation
 * @uses \Guard\Structure\Php\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric
 * @uses \Guard\Structure\Php\ClassLikeMetric\ClassLikeMetricParser
 * @uses \Guard\Structure\Php\Complexity\CyclomaticComplexityCalculator
 * @uses \Guard\Structure\Php\Complexity\CyclomaticComplexityState
 * @uses \Guard\Structure\Php\Complexity\CyclomaticDecisionWeight
 * @uses \Guard\Structure\Php\FileMetric\FileMetric
 * @uses \Guard\Structure\Php\FunctionMetric\ArrowExpressionBoundary
 * @uses \Guard\Structure\Php\FunctionMetric\ArrowFunctionMetricReader
 * @uses \Guard\Structure\Php\FunctionMetric\BlockFunctionMetricReader
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionBodyLocator
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionLineParser
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetric
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetricComplexityAssigner
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetricParser
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionNameReader
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionScanState
 * @uses \Guard\Structure\Php\FunctionMetric\NestedFunctionMetricRange
 * @uses \Guard\Structure\Php\SourceMetrics
 * @uses \Guard\Structure\Php\TokenParser
 * @uses \Guard\Structure\Php\Token\ClassLikeTokenMatcher
 * @uses \Guard\Structure\Php\Token\CodeTokenLineResolver
 * @uses \Guard\Structure\Php\Token\PhpTokenNavigator
 * @uses \Guard\Structure\Php\Token\TokenLineCounter
 * @uses \Guard\Structure\Php\Tokens
 * @uses \Guard\Structure\Source
 */
#[CoversClass(MetricParser::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(ClassLikeMetricLimit::class)]
#[UsesClass(ClassLikeMetricViolationBuilder::class)]
#[UsesClass(FileMetricViolationBuilder::class)]
#[UsesClass(FunctionComplexityViolationBuilder::class)]
#[UsesClass(FunctionLineViolationBuilder::class)]
#[UsesClass(FunctionMetricViolationBuilder::class)]
#[UsesClass(MetricLimitInspector::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
#[UsesClass(MetricViolation::class)]
#[UsesClass(ClassLikeDeclarationReader::class)]
#[UsesClass(ClassLikeMetric::class)]
#[UsesClass(ClassLikeMetricParser::class)]
#[UsesClass(CyclomaticComplexityCalculator::class)]
#[UsesClass(CyclomaticComplexityState::class)]
#[UsesClass(CyclomaticDecisionWeight::class)]
#[UsesClass(FileMetric::class)]
#[UsesClass(ArrowExpressionBoundary::class)]
#[UsesClass(ArrowFunctionMetricReader::class)]
#[UsesClass(BlockFunctionMetricReader::class)]
#[UsesClass(FunctionBodyLocator::class)]
#[UsesClass(FunctionLineParser::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(FunctionMetricComplexityAssigner::class)]
#[UsesClass(FunctionMetricParser::class)]
#[UsesClass(FunctionNameReader::class)]
#[UsesClass(FunctionScanState::class)]
#[UsesClass(NestedFunctionMetricRange::class)]
#[UsesClass(SourceMetrics::class)]
#[UsesClass(TokenParser::class)]
#[UsesClass(ClassLikeTokenMatcher::class)]
#[UsesClass(CodeTokenLineResolver::class)]
#[UsesClass(PhpTokenNavigator::class)]
#[UsesClass(TokenLineCounter::class)]
#[UsesClass(Tokens::class)]
#[UsesClass(Source::class)]
final class MetricParserTest extends TestCase
{
    /**

     */
    public function testParseReportsFileFunctionMethodAndComplexityViolations(): void
    {
        $file = sys_get_temp_dir() . '/locguard-source-' . uniqid('', true) . '.php';
        file_put_contents($file, <<<'PHP'
<?php

function long_function(): void
{
    echo '1';
    echo '2';
    echo '3';
}

final class Example
{
    public function complexMethod(int $value): void
    {
        if ($value > 0) {
            echo 'positive';
        }
        if ($value > 1 && $value < 10) {
            echo 'range';
        }
    }
}
PHP);

        $metrics = (new MetricParser())->parse((string) file_get_contents($file), 'src/Example.php');
        $violations = (new MetricLimitInspector())->violations($metrics, new LimitConfig(10, 8, 5, 50, 50, 50, 3, 4, 2, 2));

        self::assertSame('src/Example.php', $metrics->file->path);
        self::assertSame(21, $metrics->file->physicalLines);
        self::assertGreaterThan(8, $metrics->file->nonCommentLines);
        self::assertSame(
            ['file_lines', 'file_ncloc', 'class_lines', 'function_lines', 'method_lines', 'cyclomatic_complexity'],
            array_map(static fn ($violation): string => $violation->rule, $violations),
        );
    }

    /**

     */
    public function testParseAllowsValuesEqualToLimits(): void
    {
        $file = sys_get_temp_dir() . '/locguard-source-' . uniqid('', true) . '.php';
        file_put_contents($file, <<<'PHP'
<?php

function exactly_three_lines(): void
{
}
PHP);

        $metrics = (new MetricParser())->parse((string) file_get_contents($file), 'src/Example.php');
        $violations = (new MetricLimitInspector())->violations($metrics, new LimitConfig(5, 3, 50, 50, 50, 50, 3, 50, 1, 1));

        self::assertSame([], $violations);
    }

    /**

     */
    public function testParseReportsClassLikeLimitsIndividually(): void
    {
        $file = sys_get_temp_dir() . '/locguard-source-' . uniqid('', true) . '.php';
        file_put_contents($file, <<<'PHP'
<?php

class Example
{
}

trait Behavior
{
}

interface Contract
{
}

enum Status
{
    case Open;
}
PHP);

        $metrics = (new MetricParser())->parse((string) file_get_contents($file), 'src/Types.php');
        $violations = (new MetricLimitInspector())->violations($metrics, new LimitConfig(100, 100, 2, 2, 2, 2, 50, 50, 20, 20));

        self::assertSame(
            ['class_lines', 'trait_lines', 'interface_lines', 'enum_lines'],
            array_map(static fn ($violation): string => $violation->rule, $violations),
        );
    }

    /**

     */
    public function testParseKeepsPhysicalLineAndNclocLimitsSeparate(): void
    {
        $file = sys_get_temp_dir() . '/locguard-source-' . uniqid('', true) . '.php';
        file_put_contents($file, <<<'PHP'
<?php

// ignore
/*
 * ignore
 */
echo 'x';
PHP);

        $metrics = (new MetricParser())->parse((string) file_get_contents($file), 'src/Comments.php');
        $violations = (new MetricLimitInspector())->violations($metrics, new LimitConfig(4, 1, 50, 50, 50, 50, 50, 50, 20, 20));

        self::assertSame(7, $metrics->file->physicalLines);
        self::assertSame(1, $metrics->file->nonCommentLines);
        self::assertSame(['file_lines'], array_map(static fn ($violation): string => $violation->rule, $violations));
    }



    /**

     */
    public function testParseMeasuresAlreadyReadSourceWithoutFilesystemAccess(): void
    {
        $metrics = (new MetricParser())->parse("<?php\n// comment\necho 1;\n", 'memory.php');
        self::assertSame('memory.php', $metrics->file->path);
        self::assertSame(3, $metrics->file->physicalLines);
        self::assertSame(1, $metrics->file->nonCommentLines);
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function testStructureDerivesMetricsFromRegisteredTokens(): void
    {
        $source = new Source('<?php function f() { return 1; }', ['php.tokens' => new TokenParser()]);
        $metrics = (new MetricParser())->structure($source);
        self::assertCount(1, $metrics->functions);
        self::assertSame('f', $metrics->functions[0]->name);
    }
    /**

     */
    public function testMeasureAcceptsAlreadyTokenizedSourceWithoutReadingFiles(): void
    {
        $source = "<?php\n// comment\necho 1;";
        $metrics = (new MetricParser())->measure($source, array_values(PhpToken::tokenize($source)), 'inline.php');
        self::assertSame(1, $metrics->file->nonCommentLines);
        self::assertSame(3, $metrics->file->physicalLines);
    }
}
