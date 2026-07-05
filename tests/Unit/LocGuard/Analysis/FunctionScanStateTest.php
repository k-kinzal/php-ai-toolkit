<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\FunctionScanState;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FunctionScanState::class)]
final class FunctionScanStateTest extends TestCase
{
    public function testRegisterClassBodyMarksOpeningBraceAsClassContext(): void
    {
        $state = new FunctionScanState();
        $state->registerClassBody(0, 'Example');
        $state->advance(new PhpToken(123, '{', 1, 0), 0);

        self::assertTrue($state->isInClass());
        self::assertSame('Example', $state->currentClassName());
    }

    public function testRegisterFunctionBodyMarksOpeningBraceAsFunctionContext(): void
    {
        $state = new FunctionScanState();
        $state->registerFunctionBody(0);
        $state->advance(new PhpToken(123, '{', 1, 0), 0);

        self::assertFalse($state->isInClass());
        self::assertNull($state->currentClassName());
    }

    public function testIsInClassReturnsFalseBeforeClassBodyStarts(): void
    {
        self::assertFalse((new FunctionScanState())->isInClass());
    }

    public function testCurrentClassNameReturnsNullOutsideClassBody(): void
    {
        self::assertNull((new FunctionScanState())->currentClassName());
    }

    public function testAdvanceLeavesClassContextOnClosingBrace(): void
    {
        $state = new FunctionScanState();
        $state->registerClassBody(0, 'Example');
        $state->advance(new PhpToken(123, '{', 1, 0), 0);
        $state->advance(new PhpToken(125, '}', 1, 1), 1);

        self::assertFalse($state->isInClass());
        self::assertNull($state->currentClassName());
    }
}
