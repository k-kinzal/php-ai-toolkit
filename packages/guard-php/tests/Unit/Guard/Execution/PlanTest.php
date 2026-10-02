<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Execution;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Execution\Plan
 * @uses \Toolkit\Guard\Reporting\Finding
 */
#[CoversClass(\Toolkit\Guard\Execution\Plan::class)]
#[UsesClass(\Toolkit\Guard\Reporting\Finding::class)]
final class PlanTest extends TestCase
{
    public function testKeepsUnresolvedRecommendationsInThePlan(): void
    {
        $finding = new \Toolkit\Guard\Reporting\Finding('x.json', 'recommended.mode', 'recommended', 'Set mode to A.');
        $plan = new \Toolkit\Guard\Execution\Plan([$finding], []);
        self::assertSame([$finding], $plan->findings);
        self::assertSame([], $plan->changes);
    }

}
