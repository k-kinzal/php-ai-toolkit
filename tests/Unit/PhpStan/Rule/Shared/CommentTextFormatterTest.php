<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PhpAiToolkit\PhpStan\Rule\Shared\CommentTextFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CommentTextFormatter::class)]
final class CommentTextFormatterTest extends TestCase
{
    public function testTruncateTrimsAndShortensLongComments(): void
    {
        self::assertSame('/** short */', (new CommentTextFormatter())->truncate('  /** short */  '));
        self::assertStringEndsWith('...', (new CommentTextFormatter())->truncate('/** ' . str_repeat('x', 100) . ' */'));
    }
}
