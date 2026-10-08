<?php

declare(strict_types=1);

namespace Tests\Unit\Policy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Plan
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
final class PlanTest extends TestCase
{
    public function testKeepsUnresolvedRecommendationsInThePlan(): void
    {
        $finding = new \Guard\Diagnostic\Finding('x.json', 'recommended.mode', 'recommended', 'Set mode to A.');
        $plan = new \Guard\Policy\Plan([$finding], []);
        self::assertSame([$finding], $plan->findings);
        self::assertSame([], $plan->changes);
    }

}
