<?php

declare(strict_types=1);

namespace Tests\Unit\Lint;

use DecisionsLint\Lint\Assignment;
use DecisionsLint\Rule\DecisionSpec;
use DecisionsLint\Rule\Rule;
use DecisionsLint\Rule\TargetSelector;
use DecisionsLint\Source\Target;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Lint\Assignment
 * @uses \DecisionsLint\Rule\DecisionSpec
 * @uses \DecisionsLint\Rule\Rule
 * @uses \DecisionsLint\Rule\TargetSelector
 * @uses \DecisionsLint\Source\Target
 */
#[CoversClass(Assignment::class)]
#[UsesClass(DecisionSpec::class)]
#[UsesClass(Rule::class)]
#[UsesClass(TargetSelector::class)]
#[UsesClass(Target::class)]
final class AssignmentTest extends TestCase
{
    public function testExposesTheInquiryTheRuleAndTheTarget(): void
    {
        $rule = new Rule('a', '', 'error', true, [], [], new TargetSelector('method'), 'class', new DecisionSpec('predicate', 'Q?'), []);
        $target = new Target('method', 'f', 'A::f', '', 'A', 'src/A.php', 1, 1, 'function f()', 'public', [], 'D', 'C', 'F');
        $assignment = new Assignment(3, $rule, $target);
        self::assertSame(3, $assignment->inquiry);
        self::assertSame($rule, $assignment->rule);
        self::assertSame($target, $assignment->target);
    }
}
