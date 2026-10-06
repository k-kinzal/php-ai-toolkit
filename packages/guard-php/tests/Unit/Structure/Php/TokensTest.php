<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Php;

use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Php\Tokens
 */
#[CoversClass(\Guard\Structure\Php\Tokens::class)]
final class TokensTest extends TestCase
{
    /**

     */
    public function testAllPreservesTokenOffsetsAndLineNumbers(): void
    {
        $tokens = array_values(PhpToken::tokenize("<?php\n\necho 1;"));
        $subject = new \Guard\Structure\Php\Tokens($tokens);
        self::assertSame($tokens, $subject->all());
        self::assertSame(3, $subject->all()[2]->line);
    }
}
