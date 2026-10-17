<?php

declare(strict_types=1);

namespace Tests\Unit\Policy;

use Guard\Diagnostic\Finding;
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Plan
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(Plan::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Finding::class)]
final class PlanTest extends TestCase
{
    public function testKeepsUnresolvedRecommendationsInThePlan(): void
    {
        $finding = new Finding('x.json', 'recommended.mode', 'recommended', 'Set mode to A.');
        $plan = new Plan([$finding], []);
        self::assertSame([$finding], $plan->findings);
        self::assertSame([], $plan->changes);
    }

}
