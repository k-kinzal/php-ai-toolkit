<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Php;

use Guard\Config\Loc\LimitConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Php\SourceMetricReader
 * @uses \Guard\Collect\Php\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \Guard\Collect\Php\ClassLikeMetric\ClassLikeMetric
 * @uses \Guard\Collect\Php\ClassLikeMetric\ClassLikeMetricCollector
 * @uses \Guard\Collect\Php\Complexity\CyclomaticComplexityCalculator
 * @uses \Guard\Collect\Php\Complexity\CyclomaticComplexityState
 * @uses \Guard\Collect\Php\Complexity\CyclomaticDecisionWeight
 * @uses \Guard\Collect\Php\FileMetric\FileMetric
 * @uses \Guard\Collect\Php\Filesystem\FilePathPatternMatcher
 * @uses \Guard\Collect\Php\FunctionMetric\ArrowExpressionBoundary
 * @uses \Guard\Collect\Php\FunctionMetric\ArrowFunctionMetricReader
 * @uses \Guard\Collect\Php\FunctionMetric\BlockFunctionMetricReader
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionBodyLocator
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetric
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetricCollector
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetricComplexityAssigner
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetricLineCollector
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionNameReader
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionScanState
 * @uses \Guard\Collect\Php\FunctionMetric\NestedFunctionMetricRange
 * @uses \Guard\Collect\Php\PhpSources
 * @uses \Guard\Collect\Php\SourceMetrics
 * @uses \Guard\Collect\Php\Token\ClassLikeTokenMatcher
 * @uses \Guard\Collect\Php\Token\CodeTokenLineResolver
 * @uses \Guard\Collect\Php\Token\PhpTokenNavigator
 * @uses \Guard\Collect\Php\Token\TokenLineCounter
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Config\Loc\MetricsConfig
 * @uses \Guard\Config\Loc\Policy\ApplyConfig
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfig
 * @uses \Guard\Config\Loc\Policy\PolicyConfig
 * @uses \Guard\Config\Loc\ScanConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\Loc\Assignment\ApplyRuleMatcher
 * @uses \Guard\Policy\Loc\Assignment\FilePolicyAssigner
 * @uses \Guard\Policy\Loc\Assignment\FilePolicyAssignment
 * @uses \Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricLimit
 * @uses \Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricViolationBuilder
 * @uses \Guard\Policy\Loc\FileMetric\FileMetricViolationBuilder
 * @uses \Guard\Policy\Loc\FunctionMetric\FunctionComplexityViolationBuilder
 * @uses \Guard\Policy\Loc\FunctionMetric\FunctionLineViolationBuilder
 * @uses \Guard\Policy\Loc\FunctionMetric\FunctionMetricViolationBuilder
 * @uses \Guard\Policy\Loc\LocPolicy
 * @uses \Guard\Policy\Loc\Violation
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Collect\Php\SourceMetricReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\ClassLikeMetric\ClassLikeDeclarationReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\ClassLikeMetric\ClassLikeMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\ClassLikeMetric\ClassLikeMetricCollector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Complexity\CyclomaticComplexityCalculator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Complexity\CyclomaticComplexityState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Complexity\CyclomaticDecisionWeight::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FileMetric\FileMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Filesystem\FilePathPatternMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\ArrowExpressionBoundary::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\ArrowFunctionMetricReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\BlockFunctionMetricReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionBodyLocator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionMetricCollector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionMetricComplexityAssigner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionMetricLineCollector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionNameReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionScanState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\NestedFunctionMetricRange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\PhpSources::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\SourceMetrics::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Token\ClassLikeTokenMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Token\CodeTokenLineResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Token\PhpTokenNavigator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Token\TokenLineCounter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(LimitConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\MetricsConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\ApplyConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\ApplyRuleConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\PolicyConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\ScanConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\Assignment\ApplyRuleMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\Assignment\FilePolicyAssigner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\Assignment\FilePolicyAssignment::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricLimit::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\FileMetric\FileMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\FunctionMetric\FunctionComplexityViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\FunctionMetric\FunctionLineViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\FunctionMetric\FunctionMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\LocPolicy::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\Violation::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
final class SourceMetricReaderTest extends TestCase
{
    public function testReadReportsFileFunctionMethodAndComplexityViolations(): void
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

        $metrics = (new \Guard\Collect\Php\SourceMetricReader())->read($file, 'src/Example.php');
        $violations = (new \Guard\Policy\Loc\LocPolicy())->violations($metrics, new LimitConfig(10, 8, 5, 50, 50, 50, 3, 4, 2, 2));

        self::assertSame('src/Example.php', $metrics->file->path);
        self::assertSame(21, $metrics->file->physicalLines);
        self::assertGreaterThan(8, $metrics->file->nonCommentLines);
        self::assertSame(
            ['file_lines', 'file_ncloc', 'class_lines', 'function_lines', 'method_lines', 'cyclomatic_complexity'],
            array_map(static fn ($violation): string => $violation->rule, $violations),
        );
    }

    public function testReadAllowsValuesEqualToLimits(): void
    {
        $file = sys_get_temp_dir() . '/locguard-source-' . uniqid('', true) . '.php';
        file_put_contents($file, <<<'PHP'
<?php

function exactly_three_lines(): void
{
}
PHP);

        $metrics = (new \Guard\Collect\Php\SourceMetricReader())->read($file, 'src/Example.php');
        $violations = (new \Guard\Policy\Loc\LocPolicy())->violations($metrics, new LimitConfig(5, 3, 50, 50, 50, 50, 3, 50, 1, 1));

        self::assertSame([], $violations);
    }

    public function testReadReportsClassLikeLimitsIndividually(): void
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

        $metrics = (new \Guard\Collect\Php\SourceMetricReader())->read($file, 'src/Types.php');
        $violations = (new \Guard\Policy\Loc\LocPolicy())->violations($metrics, new LimitConfig(100, 100, 2, 2, 2, 2, 50, 50, 20, 20));

        self::assertSame(
            ['class_lines', 'trait_lines', 'interface_lines', 'enum_lines'],
            array_map(static fn ($violation): string => $violation->rule, $violations),
        );
    }

    public function testReadKeepsPhysicalLineAndNclocLimitsSeparate(): void
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

        $metrics = (new \Guard\Collect\Php\SourceMetricReader())->read($file, 'src/Comments.php');
        $violations = (new \Guard\Policy\Loc\LocPolicy())->violations($metrics, new LimitConfig(4, 1, 50, 50, 50, 50, 50, 50, 20, 20));

        self::assertSame(7, $metrics->file->physicalLines);
        self::assertSame(1, $metrics->file->nonCommentLines);
        self::assertSame(['file_lines'], array_map(static fn ($violation): string => $violation->rule, $violations));
    }



    public function testParseMeasuresAlreadyReadSourceWithoutFilesystemAccess(): void
    {
        $metrics = (new \Guard\Collect\Php\SourceMetricReader())->parse("<?php\n// comment\necho 1;\n", 'memory.php');
        self::assertSame('memory.php', $metrics->file->path);
        self::assertSame(3, $metrics->file->physicalLines);
        self::assertSame(1, $metrics->file->nonCommentLines);
    }
}
