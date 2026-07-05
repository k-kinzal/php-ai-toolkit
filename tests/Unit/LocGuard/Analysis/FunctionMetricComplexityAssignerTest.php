<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\CyclomaticComplexityCalculator;
use PhpAiToolkit\LocGuard\Analysis\CyclomaticComplexityState;
use PhpAiToolkit\LocGuard\Analysis\CyclomaticDecisionWeight;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetricComplexityAssigner;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetricLineCollector;
use PhpAiToolkit\LocGuard\Analysis\NestedFunctionMetricRange;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FunctionMetricComplexityAssigner::class)]
#[UsesClass(CyclomaticComplexityCalculator::class)]
#[UsesClass(CyclomaticComplexityState::class)]
#[UsesClass(CyclomaticDecisionWeight::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(FunctionMetricLineCollector::class)]
#[UsesClass(NestedFunctionMetricRange::class)]
final class FunctionMetricComplexityAssignerTest extends TestCase
{
    public function testAssignFillsCyclomaticComplexityOnMetrics(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

function run(bool $enabled): void
{
    if ($enabled) {
    }
}
PHP, TOKEN_PARSE));
        $metrics = (new FunctionMetricLineCollector())->collect($tokens);

        (new FunctionMetricComplexityAssigner())->assign($tokens, $metrics);

        self::assertSame(2, $metrics[0]->cyclomaticComplexity);
    }
}
