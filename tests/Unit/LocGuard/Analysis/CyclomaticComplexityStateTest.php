<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\CyclomaticComplexityState;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use const T_MATCH;

#[CoversClass(CyclomaticComplexityState::class)]
final class CyclomaticComplexityStateTest extends TestCase
{
    public function testIsAtMatchArmReturnsTrueInsideMatchArmList(): void
    {
        $state = new CyclomaticComplexityState();
        $state->advance(new PhpToken(T_MATCH, 'match', 1, 0));
        $state->advance(new PhpToken(40, '(', 1, 5));
        $state->advance(new PhpToken(41, ')', 1, 6));
        $state->advance(new PhpToken(123, '{', 1, 8));

        self::assertTrue($state->isAtMatchArm());
    }

    public function testAdvanceLeavesMatchArmListAfterClosingBrace(): void
    {
        $state = new CyclomaticComplexityState();
        $state->advance(new PhpToken(T_MATCH, 'match', 1, 0));
        $state->advance(new PhpToken(40, '(', 1, 5));
        $state->advance(new PhpToken(41, ')', 1, 6));
        $state->advance(new PhpToken(123, '{', 1, 8));
        $state->advance(new PhpToken(125, '}', 1, 9));

        self::assertFalse($state->isAtMatchArm());
    }
}
