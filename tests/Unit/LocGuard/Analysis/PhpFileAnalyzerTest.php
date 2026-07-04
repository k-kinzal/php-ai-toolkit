<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric;
use PhpAiToolkit\LocGuard\Analysis\ClassLikeMetricCollector;
use PhpAiToolkit\LocGuard\Analysis\CyclomaticComplexityCalculator;
use PhpAiToolkit\LocGuard\Analysis\FileAnalysis;
use PhpAiToolkit\LocGuard\Analysis\FileMetric;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetricCollector;
use PhpAiToolkit\LocGuard\Analysis\PhpFileAnalyzer;
use PhpAiToolkit\LocGuard\Analysis\TokenLineCounter;
use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Config\LimitConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpFileAnalyzer::class)]
#[UsesClass(ClassLikeMetric::class)]
#[UsesClass(ClassLikeMetricCollector::class)]
#[UsesClass(CyclomaticComplexityCalculator::class)]
#[UsesClass(FileAnalysis::class)]
#[UsesClass(FileMetric::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(FunctionMetricCollector::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(TokenLineCounter::class)]
#[UsesClass(Violation::class)]
final class PhpFileAnalyzerTest extends TestCase
{
    public function testAnalyzeReportsFileFunctionMethodAndComplexityViolations(): void
    {
        $file = sys_get_temp_dir() . '/locguard-source-' . bin2hex(random_bytes(4)) . '.php';
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
            new LimitConfig(10, 8, 5, 50, 50, 50, 3, 4, 2),
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
        $file = sys_get_temp_dir() . '/locguard-source-' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($file, <<<'PHP'
<?php

function exactly_three_lines(): void
{
}
PHP);

        $analysis = (new PhpFileAnalyzer())->analyze(
            $file,
            'src/Example.php',
            new LimitConfig(5, 3, 50, 50, 50, 50, 3, 50, 1),
        );

        self::assertSame([], $analysis->violations);
    }

    public function testAnalyzeReportsClassLikeLimitsIndividually(): void
    {
        $file = sys_get_temp_dir() . '/locguard-source-' . bin2hex(random_bytes(4)) . '.php';
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
            new LimitConfig(100, 100, 2, 2, 2, 2, 50, 50, 20),
        );

        self::assertSame(
            ['class_lines', 'trait_lines', 'interface_lines', 'enum_lines'],
            array_map(static fn ($violation): string => $violation->rule, $analysis->violations),
        );
    }

    public function testAnalyzeKeepsPhysicalLineAndNclocLimitsSeparate(): void
    {
        $file = sys_get_temp_dir() . '/locguard-source-' . bin2hex(random_bytes(4)) . '.php';
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
            new LimitConfig(4, 1, 50, 50, 50, 50, 50, 50, 20),
        );

        self::assertSame(7, $analysis->file->physicalLines);
        self::assertSame(1, $analysis->file->nonCommentLines);
        self::assertSame(['file_lines'], array_map(static fn ($violation): string => $violation->rule, $analysis->violations));
    }
}
