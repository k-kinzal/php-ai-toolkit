<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Php;

use Guard\Policy\Definition\LimitConfig;
use JsonException;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
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
#[CoversClass(\Guard\Structure\Php\MetricParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(LimitConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\ClassLikeMetricLimit::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\ClassLikeMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\FileMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\FunctionComplexityViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\FunctionLineViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\FunctionMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\MetricLimitInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\Finding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Diagnostic\MetricViolation::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\ClassLikeMetric\ClassLikeDeclarationReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\ClassLikeMetric\ClassLikeMetricParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Complexity\CyclomaticComplexityCalculator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Complexity\CyclomaticComplexityState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Complexity\CyclomaticDecisionWeight::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FileMetric\FileMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\ArrowExpressionBoundary::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\ArrowFunctionMetricReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\BlockFunctionMetricReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionBodyLocator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionLineParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionMetricComplexityAssigner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionMetricParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionNameReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionScanState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\NestedFunctionMetricRange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\SourceMetrics::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\TokenParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Token\ClassLikeTokenMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Token\CodeTokenLineResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Token\PhpTokenNavigator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Token\TokenLineCounter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Tokens::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Source::class)]
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

        $metrics = (new \Guard\Structure\Php\MetricParser())->parse((string) file_get_contents($file), 'src/Example.php');
        $violations = (new \Guard\Policy\Limit\MetricLimitInspector())->violations($metrics, new LimitConfig(10, 8, 5, 50, 50, 50, 3, 4, 2, 2));

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

        $metrics = (new \Guard\Structure\Php\MetricParser())->parse((string) file_get_contents($file), 'src/Example.php');
        $violations = (new \Guard\Policy\Limit\MetricLimitInspector())->violations($metrics, new LimitConfig(5, 3, 50, 50, 50, 50, 3, 50, 1, 1));

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

        $metrics = (new \Guard\Structure\Php\MetricParser())->parse((string) file_get_contents($file), 'src/Types.php');
        $violations = (new \Guard\Policy\Limit\MetricLimitInspector())->violations($metrics, new LimitConfig(100, 100, 2, 2, 2, 2, 50, 50, 20, 20));

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

        $metrics = (new \Guard\Structure\Php\MetricParser())->parse((string) file_get_contents($file), 'src/Comments.php');
        $violations = (new \Guard\Policy\Limit\MetricLimitInspector())->violations($metrics, new LimitConfig(4, 1, 50, 50, 50, 50, 50, 50, 20, 20));

        self::assertSame(7, $metrics->file->physicalLines);
        self::assertSame(1, $metrics->file->nonCommentLines);
        self::assertSame(['file_lines'], array_map(static fn ($violation): string => $violation->rule, $violations));
    }



    /**

     */
    public function testParseMeasuresAlreadyReadSourceWithoutFilesystemAccess(): void
    {
        $metrics = (new \Guard\Structure\Php\MetricParser())->parse("<?php\n// comment\necho 1;\n", 'memory.php');
        self::assertSame('memory.php', $metrics->file->path);
        self::assertSame(3, $metrics->file->physicalLines);
        self::assertSame(1, $metrics->file->nonCommentLines);
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testStructureDerivesMetricsFromRegisteredTokens(): void
    {
        $source = new \Guard\Structure\Source('<?php function f() { return 1; }', ['php.tokens' => new \Guard\Structure\Php\TokenParser()]);
        $metrics = (new \Guard\Structure\Php\MetricParser())->structure($source);
        self::assertCount(1, $metrics->functions);
        self::assertSame('f', $metrics->functions[0]->name);
    }
    /**

     */
    public function testMeasureAcceptsAlreadyTokenizedSourceWithoutReadingFiles(): void
    {
        $source = "<?php\n// comment\necho 1;";
        $metrics = (new \Guard\Structure\Php\MetricParser())->measure($source, array_values(PhpToken::tokenize($source)), 'inline.php');
        self::assertSame(1, $metrics->file->nonCommentLines);
        self::assertSame(3, $metrics->file->physicalLines);
    }
}
