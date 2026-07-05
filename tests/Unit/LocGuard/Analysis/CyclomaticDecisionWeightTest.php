<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\CyclomaticComplexityState;
use PhpAiToolkit\LocGuard\Analysis\CyclomaticDecisionWeight;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use const T_DOUBLE_ARROW;
use const T_IF;
use const T_MATCH;

#[CoversClass(CyclomaticDecisionWeight::class)]
#[UsesClass(CyclomaticComplexityState::class)]
final class CyclomaticDecisionWeightTest extends TestCase
{
    public function testWeightCountsBranchToken(): void
    {
        self::assertSame(1, (new CyclomaticDecisionWeight())->weight(new PhpToken(T_IF, 'if', 1, 0), new CyclomaticComplexityState()));
    }

    public function testWeightCountsMatchArmDoubleArrowOnlyInsideMatch(): void
    {
        $state = new CyclomaticComplexityState();
        $state->advance(new PhpToken(T_MATCH, 'match', 1, 0));
        $state->advance(new PhpToken(40, '(', 1, 5));
        $state->advance(new PhpToken(41, ')', 1, 6));
        $state->advance(new PhpToken(123, '{', 1, 8));

        self::assertSame(1, (new CyclomaticDecisionWeight())->weight(new PhpToken(T_DOUBLE_ARROW, '=>', 1, 10), $state));
        self::assertSame(0, (new CyclomaticDecisionWeight())->weight(new PhpToken(T_DOUBLE_ARROW, '=>', 1, 10), new CyclomaticComplexityState()));
    }

    public function testDecisionTokenIdsReturnsBranchTokenIds(): void
    {
        self::assertContains(T_IF, (new CyclomaticDecisionWeight())->decisionTokenIds());
    }
}
