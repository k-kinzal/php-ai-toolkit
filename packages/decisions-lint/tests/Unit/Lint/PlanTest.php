<?php

declare(strict_types=1);

namespace Tests\Unit\Lint;

use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Lint\Assignment;
use DecisionsLint\Lint\Plan;
use DecisionsLint\Rule\DecisionSpec;
use DecisionsLint\Rule\Rule;
use DecisionsLint\Rule\TargetSelector;
use DecisionsLint\Source\Target;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Lint\Plan
 * @uses \DecisionsLint\Decision\Inquiry
 * @uses \DecisionsLint\Lint\Assignment
 * @uses \DecisionsLint\Rule\DecisionSpec
 * @uses \DecisionsLint\Rule\Rule
 * @uses \DecisionsLint\Rule\TargetSelector
 * @uses \DecisionsLint\Source\Target
 */
#[CoversClass(Plan::class)]
#[UsesClass(Inquiry::class)]
#[UsesClass(Assignment::class)]
#[UsesClass(DecisionSpec::class)]
#[UsesClass(Rule::class)]
#[UsesClass(TargetSelector::class)]
#[UsesClass(Target::class)]
final class PlanTest extends TestCase
{
    public function testTargetsCountsDistinctDeclarations(): void
    {
        $rule = new Rule('a', '', 'error', true, [], [], new TargetSelector('method'), 'class', new DecisionSpec('predicate', 'Q?'), []);
        $other = new Rule('b', '', 'error', true, [], [], new TargetSelector('method'), 'declaration', new DecisionSpec('predicate', 'Q?'), []);
        $first = new Target('method', 'f', 'A::f', '', 'A', 'src/A.php', 1, 1, 'function f()', 'public', [], 'D', 'C', 'F');
        $second = new Target('method', 'g', 'A::g', '', 'A', 'src/A.php', 5, 1, 'function g()', 'public', [], 'D', 'C', 'F');
        $plan = new Plan(['src/A.php'], [new Inquiry([], []), new Inquiry([], []), new Inquiry([], [])], [new Assignment(0, $rule, $first), new Assignment(1, $other, $first), new Assignment(2, $rule, $second)]);
        self::assertSame(2, $plan->targets());
        self::assertSame(['src/A.php'], $plan->files);
        self::assertCount(3, $plan->inquiries);
        self::assertCount(3, $plan->assignments);
    }
}
