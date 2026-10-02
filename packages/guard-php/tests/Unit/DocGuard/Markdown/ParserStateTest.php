<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Markdown;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Markdown\Fence;
use Toolkit\DocGuard\Markdown\ParserState;

/**
 * @covers \Toolkit\DocGuard\Markdown\ParserState
 * @uses \Toolkit\DocGuard\Markdown\Fence
 */
#[CoversClass(ParserState::class)]
#[UsesClass(Fence::class)]
final class ParserStateTest extends TestCase
{
    public function testInterruptClosesParagraphAndLeavesListAtColumnZero(): void
    {
        $state = new ParserState();
        $state->paragraph = ['text'];
        $state->listContext = true;

        $state->interrupt(0);

        self::assertSame([], $state->paragraph);
        self::assertFalse($state->listContext);
    }

    public function testInterruptKeepsListContextWhenIndented(): void
    {
        $state = new ParserState();
        $state->paragraph = ['text'];
        $state->listContext = true;

        $state->interrupt(2);

        self::assertSame([], $state->paragraph);
        self::assertTrue($state->listContext);
    }
}
