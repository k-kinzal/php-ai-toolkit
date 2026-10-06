<?php

declare(strict_types=1);

namespace Tests\Unit\Execution;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Execution\Plan
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class PlanTest extends TestCase
{
    public function testKeepsUnresolvedRecommendationsInThePlan(): void
    {
        $finding = new \Guard\Reporting\Finding('x.json', 'recommended.mode', 'recommended', 'Set mode to A.');
        $plan = new \Guard\Execution\Plan([$finding], []);
        self::assertSame([$finding], $plan->findings);
        self::assertSame([], $plan->changes);
    }

}
