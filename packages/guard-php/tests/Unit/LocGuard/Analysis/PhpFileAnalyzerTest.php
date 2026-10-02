<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeDeclarationReader;
use Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetric;
use Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricCollector;
use Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricLimit;
use Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricViolationBuilder;
use Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityCalculator;
use Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityState;
use Toolkit\LocGuard\Analysis\Complexity\CyclomaticDecisionWeight;
use Toolkit\LocGuard\Analysis\FileAnalysis;
use Toolkit\LocGuard\Analysis\FileMetric\FileMetric;
use Toolkit\LocGuard\Analysis\FileMetric\FileMetricViolationBuilder;
use Toolkit\LocGuard\Analysis\FunctionMetric\ArrowFunctionMetricReader;
use Toolkit\LocGuard\Analysis\FunctionMetric\BlockFunctionMetricReader;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionComplexityViolationBuilder;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionLineViolationBuilder;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricCollector;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricComplexityAssigner;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricLineCollector;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricViolationBuilder;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionNameReader;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionScanState;
use Toolkit\LocGuard\Analysis\FunctionMetric\NestedFunctionMetricRange;
use Toolkit\LocGuard\Analysis\PhpFileAnalyzer;
use Toolkit\LocGuard\Analysis\Token\ClassLikeTokenMatcher;
use Toolkit\LocGuard\Analysis\Token\CodeTokenLineResolver;
use Toolkit\LocGuard\Analysis\Token\PhpTokenNavigator;
use Toolkit\LocGuard\Analysis\Token\TokenLineCounter;
use Toolkit\LocGuard\Analysis\Violation;
use Toolkit\LocGuard\Config\LimitConfig;

/**
 * @covers \Toolkit\LocGuard\Analysis\PhpFileAnalyzer
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\ArrowFunctionMetricReader
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\BlockFunctionMetricReader
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetric
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricCollector
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricLimit
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\Token\ClassLikeTokenMatcher
 * @uses \Toolkit\LocGuard\Analysis\Token\CodeTokenLineResolver
 * @uses \Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityCalculator
 * @uses \Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityState
 * @uses \Toolkit\LocGuard\Analysis\Complexity\CyclomaticDecisionWeight
 * @uses \Toolkit\LocGuard\Analysis\FileAnalysis
 * @uses \Toolkit\LocGuard\Analysis\FileMetric\FileMetric
 * @uses \Toolkit\LocGuard\Analysis\FileMetric\FileMetricViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionComplexityViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionLineViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricCollector
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricComplexityAssigner
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricLineCollector
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionNameReader
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionScanState
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\NestedFunctionMetricRange
 * @uses \Toolkit\LocGuard\Analysis\Token\PhpTokenNavigator
 * @uses \Toolkit\LocGuard\Analysis\Token\TokenLineCounter
 * @uses \Toolkit\LocGuard\Analysis\Violation
 */
#[CoversClass(PhpFileAnalyzer::class)]
#[UsesClass(ArrowFunctionMetricReader::class)]
#[UsesClass(BlockFunctionMetricReader::class)]
#[UsesClass(ClassLikeDeclarationReader::class)]
#[UsesClass(ClassLikeMetric::class)]
#[UsesClass(ClassLikeMetricCollector::class)]
#[UsesClass(ClassLikeMetricLimit::class)]
#[UsesClass(ClassLikeMetricViolationBuilder::class)]
#[UsesClass(ClassLikeTokenMatcher::class)]
#[UsesClass(CodeTokenLineResolver::class)]
#[UsesClass(CyclomaticComplexityCalculator::class)]
#[UsesClass(CyclomaticComplexityState::class)]
#[UsesClass(CyclomaticDecisionWeight::class)]
#[UsesClass(FileAnalysis::class)]
#[UsesClass(FileMetric::class)]
#[UsesClass(FileMetricViolationBuilder::class)]
#[UsesClass(FunctionBodyLocator::class)]
#[UsesClass(FunctionComplexityViolationBuilder::class)]
#[UsesClass(FunctionLineViolationBuilder::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(FunctionMetricCollector::class)]
#[UsesClass(FunctionMetricComplexityAssigner::class)]
#[UsesClass(FunctionMetricLineCollector::class)]
#[UsesClass(FunctionMetricViolationBuilder::class)]
#[UsesClass(FunctionNameReader::class)]
#[UsesClass(FunctionScanState::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(NestedFunctionMetricRange::class)]
#[UsesClass(PhpTokenNavigator::class)]
#[UsesClass(TokenLineCounter::class)]
#[UsesClass(Violation::class)]
final class PhpFileAnalyzerTest extends TestCase
{
    public function testAnalyzeReportsFileFunctionMethodAndComplexityViolations(): void
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

        $analysis = (new PhpFileAnalyzer())->analyze(
            $file,
            'src/Example.php',
            new LimitConfig(10, 8, 5, 50, 50, 50, 3, 4, 2, 2),
        );

        self::assertSame('src/Example.php', $analysis->file->path);
        self::assertSame(21, $analysis->file->physicalLines);
        self::assertGreaterThan(8, $analysis->file->nonCommentLines);
        self::assertSame(
            ['file_lines', 'file_ncloc', 'class_lines', 'function_lines', 'method_lines', 'cyclomatic_complexity'],
            array_map(static fn ($violation): string => $violation->rule, $analysis->violations),
        );
    }

    public function testAnalyzeAllowsValuesEqualToLimits(): void
    {
        $file = sys_get_temp_dir() . '/locguard-source-' . uniqid('', true) . '.php';
        file_put_contents($file, <<<'PHP'
<?php

function exactly_three_lines(): void
{
}
PHP);

        $analysis = (new PhpFileAnalyzer())->analyze(
            $file,
            'src/Example.php',
            new LimitConfig(5, 3, 50, 50, 50, 50, 3, 50, 1, 1),
        );

        self::assertSame([], $analysis->violations);
    }

    public function testAnalyzeReportsClassLikeLimitsIndividually(): void
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

        $analysis = (new PhpFileAnalyzer())->analyze(
            $file,
            'src/Types.php',
            new LimitConfig(100, 100, 2, 2, 2, 2, 50, 50, 20, 20),
        );

        self::assertSame(
            ['class_lines', 'trait_lines', 'interface_lines', 'enum_lines'],
            array_map(static fn ($violation): string => $violation->rule, $analysis->violations),
        );
    }

    public function testAnalyzeKeepsPhysicalLineAndNclocLimitsSeparate(): void
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

        $analysis = (new PhpFileAnalyzer())->analyze(
            $file,
            'src/Comments.php',
            new LimitConfig(4, 1, 50, 50, 50, 50, 50, 50, 20, 20),
        );

        self::assertSame(7, $analysis->file->physicalLines);
        self::assertSame(1, $analysis->file->nonCommentLines);
        self::assertSame(['file_lines'], array_map(static fn ($violation): string => $violation->rule, $analysis->violations));
    }
}
