<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\CodeTokenLineResolver;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use const T_COMMENT;
use const T_OPEN_TAG;
use const T_STRING;
use const T_WHITESPACE;

#[CoversClass(CodeTokenLineResolver::class)]
final class CodeTokenLineResolverTest extends TestCase
{
    public function testLineNumbersReturnsEmptyForWhitespaceCommentsAndPhpTags(): void
    {
        $resolver = new CodeTokenLineResolver();

        self::assertSame([], $resolver->lineNumbers(new PhpToken(T_WHITESPACE, "\n", 1, 0)));
        self::assertSame([], $resolver->lineNumbers(new PhpToken(T_COMMENT, '// comment', 2, 1)));
        self::assertSame([], $resolver->lineNumbers(new PhpToken(T_OPEN_TAG, '<?php', 3, 11)));
    }

    public function testLineNumbersReturnsCodeLineNumbersForMultilineToken(): void
    {
        $token = new PhpToken(T_STRING, "alpha\nbeta", 8, 0);

        self::assertSame([8, 9], (new CodeTokenLineResolver())->lineNumbers($token));
    }

    public function testLineNumbersIgnoresBlankPartsInsideMultilineToken(): void
    {
        $token = new PhpToken(T_STRING, "alpha\n\nbeta", 8, 0);

        self::assertSame([8, 10], (new CodeTokenLineResolver())->lineNumbers($token));
    }
}
