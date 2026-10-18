<?php

declare(strict_types=1);

namespace Tests\Unit\Source;

use DecisionsLint\Source\Tokens;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Source\Tokens
 */
#[CoversClass(Tokens::class)]
final class TokensTest extends TestCase
{
    public function testCountReturnsTheNumberOfTokens(): void
    {
        self::assertSame(3, (new Tokens("<?php\n\$a;"))->count());
    }

    public function testAtReturnsTheTokenAtAnIndex(): void
    {
        self::assertSame('$a', (new Tokens("<?php\n\$a;"))->at(1)->text);
    }

    public function testNextSignificantSkipsWhitespaceAndComments(): void
    {
        $tokens = new Tokens("<?php\n/** doc */ // note\n\$a;");
        self::assertSame('$a', $tokens->at((int) $tokens->nextSignificant(0))->text);
        self::assertNull($tokens->nextSignificant($tokens->count() - 1));
    }

    public function testPreviousSignificantSkipsWhitespaceAndComments(): void
    {
        $tokens = new Tokens("<?php\nfoo /* x */ ;");
        $semicolon = $tokens->count() - 1;
        self::assertSame('foo', $tokens->at((int) $tokens->previousSignificant($semicolon))->text);
        self::assertNull($tokens->previousSignificant(0));
    }

    public function testClosingBalancesBracesIncludingInterpolationOpeners(): void
    {
        $tokens = new Tokens("<?php\nfunction f() { if (\$x) { echo \"{\$y}\"; } }\n\$after;");
        $open = (int) $tokens->find(0, ['{']);
        $close = $tokens->closing($open);
        self::assertSame('}', $tokens->at($close)->text);
        self::assertSame('$after', $tokens->at((int) $tokens->nextSignificant($close))->text);
        self::assertSame(')', $tokens->at($tokens->closing((int) $tokens->find(0, ['(']), '(', ')'))->text);
    }

    public function testClosingReturnsTheLastIndexWhenUnbalanced(): void
    {
        $tokens = new Tokens("<?php\nfunction f() {");
        self::assertSame($tokens->count() - 1, $tokens->closing((int) $tokens->find(0, ['{'])));
    }

    public function testOpensBraceRecognisesInterpolationOpeners(): void
    {
        $tokens = new Tokens("<?php\necho \"{\$y} \${z}\";");
        self::assertTrue($tokens->opensBrace($tokens->at((int) $tokens->find(0, ['{']))));
        self::assertTrue($tokens->opensBrace($tokens->at((int) $tokens->find(0, ['${']))));
        self::assertFalse($tokens->opensBrace($tokens->at(0)));
    }

    public function testFindReturnsTheFirstMatchingTextOrNull(): void
    {
        $tokens = new Tokens("<?php\n\$a = 1; \$b = 2;");
        self::assertSame(';', $tokens->at((int) $tokens->find(0, [';']))->text);
        self::assertNull($tokens->find(0, ['{']));
    }

    public function testColumnIsOneBasedWithinTheLine(): void
    {
        $tokens = new Tokens("<?php\n    public function f() {}");
        $public = (int) $tokens->find(0, ['public']);
        self::assertSame(5, $tokens->column($public));
        self::assertSame(1, $tokens->column(0));
    }

    public function testTextReturnsTheSourceBetweenTwoTokensInclusive(): void
    {
        $tokens = new Tokens("<?php\nfunction f() {}");
        self::assertSame('function f', $tokens->text(1, 3));
    }
}
