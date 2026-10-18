<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use DecisionsLint\Rule\DecisionSpec;
use DecisionsLint\Rule\Rule;
use DecisionsLint\Rule\TargetSelector;
use DecisionsLint\Rule\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Rule\Rule
 * @uses \DecisionsLint\Rule\DecisionSpec
 * @uses \DecisionsLint\Rule\TargetSelector
 * @uses \DecisionsLint\Rule\Violation
 */
#[CoversClass(Rule::class)]
#[UsesClass(DecisionSpec::class)]
#[UsesClass(TargetSelector::class)]
#[UsesClass(Violation::class)]
final class RuleTest extends TestCase
{
    public function testExposesEveryPartOfTheRule(): void
    {
        $target = new TargetSelector('method');
        $decision = new DecisionSpec('predicate', 'Q?');
        $violation = new Violation(false, null, null, 'M', 'F');
        $rule = new Rule('a.b', 'Desc', 'warning', false, ['src/**'], ['src/Gen/**'], $target, 'file', $decision, [$violation]);
        self::assertSame('a.b', $rule->id);
        self::assertSame('Desc', $rule->description);
        self::assertSame('warning', $rule->severity);
        self::assertFalse($rule->enabled);
        self::assertSame(['src/**'], $rule->paths);
        self::assertSame(['src/Gen/**'], $rule->exclude);
        self::assertSame($target, $rule->target);
        self::assertSame('file', $rule->context);
        self::assertSame($decision, $rule->decision);
        self::assertSame([$violation], $rule->violations);
    }
}
