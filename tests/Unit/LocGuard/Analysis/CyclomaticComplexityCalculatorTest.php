<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\CyclomaticComplexityCalculator;
use PhpAiToolkit\LocGuard\Analysis\CyclomaticComplexityState;
use PhpAiToolkit\LocGuard\Analysis\CyclomaticDecisionWeight;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetricCollector;
use PhpAiToolkit\LocGuard\Analysis\NestedFunctionMetricRange;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CyclomaticComplexityCalculator::class)]
#[UsesClass(CyclomaticComplexityState::class)]
#[UsesClass(CyclomaticDecisionWeight::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(FunctionMetricCollector::class)]
#[UsesClass(NestedFunctionMetricRange::class)]
final class CyclomaticComplexityCalculatorTest extends TestCase
{
    public function testCalculateCountsBranchTokensAndTopLevelMatchArms(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

function map_value(int $value, bool $enabled): array
{
    if ($enabled && $value > 0) {
        return match ($value) {
            1 => ['nested' => 1],
            default => ['nested' => 0],
        };
    }

    return $value > 0 ? ['fallback' => $value] : [];
}
PHP, TOKEN_PARSE));
        $metrics = (new FunctionMetricCollector())->collect($tokens);

        self::assertSame(6, (new CyclomaticComplexityCalculator())->calculate($tokens, $metrics[0], $metrics));
    }

    public function testCalculateExcludesNestedFunctionMetrics(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

function outer(): void
{
    if (true) {
        echo 'outer';
    }
    $inner = fn (int $value): int => $value > 0 ? $value : 0;
}
PHP, TOKEN_PARSE));
        $metrics = (new FunctionMetricCollector())->collect($tokens);

        self::assertSame(2, (new CyclomaticComplexityCalculator())->calculate($tokens, $metrics[0], $metrics));
        self::assertSame(2, (new CyclomaticComplexityCalculator())->calculate($tokens, $metrics[1], $metrics));
    }
}
