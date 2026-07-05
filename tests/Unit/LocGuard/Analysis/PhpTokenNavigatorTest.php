<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\PhpTokenNavigator;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_STRING;
use const T_WHITESPACE;

#[CoversClass(PhpTokenNavigator::class)]
final class PhpTokenNavigatorTest extends TestCase
{
    public function testPreviousSignificantReturnsPreviousNonTriviaToken(): void
    {
        $tokens = [
            new PhpToken(T_STRING, 'first', 1, 0),
            new PhpToken(T_WHITESPACE, "\n", 1, 5),
            new PhpToken(T_COMMENT, '// comment', 2, 6),
            new PhpToken(T_DOC_COMMENT, '/** doc */', 3, 17),
            new PhpToken(T_STRING, 'second', 4, 28),
        ];

        $previous = (new PhpTokenNavigator())->previousSignificant($tokens, 4);

        self::assertSame('first', $previous?->text);
    }

    public function testPreviousSignificantReturnsNullWhenNoPreviousTokenExists(): void
    {
        $tokens = [
            new PhpToken(T_STRING, 'first', 1, 0),
        ];

        self::assertNull((new PhpTokenNavigator())->previousSignificant($tokens, 0));
    }

    public function testNextSignificantReturnsNextNonTriviaToken(): void
    {
        $tokens = [
            new PhpToken(T_STRING, 'first', 1, 0),
            new PhpToken(T_WHITESPACE, "\n", 1, 5),
            new PhpToken(T_COMMENT, '// comment', 2, 6),
            new PhpToken(T_DOC_COMMENT, '/** doc */', 3, 17),
            new PhpToken(T_STRING, 'second', 4, 28),
        ];

        $next = (new PhpTokenNavigator())->nextSignificant($tokens, 0);

        self::assertSame('second', $next?->text);
    }

    public function testNextSignificantReturnsNullWhenNoNextTokenExists(): void
    {
        $tokens = [
            new PhpToken(T_STRING, 'first', 1, 0),
        ];

        self::assertNull((new PhpTokenNavigator())->nextSignificant($tokens, 0));
    }

    public function testPreviousSignificantIndexReturnsPreviousNonTriviaTokenIndex(): void
    {
        $tokens = [
            new PhpToken(T_STRING, 'first', 1, 0),
            new PhpToken(T_WHITESPACE, "\n", 1, 5),
            new PhpToken(T_STRING, 'second', 2, 6),
        ];

        self::assertSame(0, (new PhpTokenNavigator())->previousSignificantIndex($tokens, 2));
    }

    public function testPreviousSignificantIndexReturnsNullWhenNoPreviousTokenExists(): void
    {
        $tokens = [
            new PhpToken(T_STRING, 'first', 1, 0),
        ];

        self::assertNull((new PhpTokenNavigator())->previousSignificantIndex($tokens, 0));
    }

    public function testNextTextReturnsMatchingTextIndex(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php final class Sample {}', TOKEN_PARSE));

        self::assertSame(7, (new PhpTokenNavigator())->nextText($tokens, 0, '{'));
    }

    public function testNextTextReturnsNullWhenTextIsMissing(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php final class Sample {}', TOKEN_PARSE));

        self::assertNull((new PhpTokenNavigator())->nextText($tokens, 0, '['));
    }

    public function testNextIdReturnsMatchingTokenIndex(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php final class Sample {}', TOKEN_PARSE));

        self::assertSame(5, (new PhpTokenNavigator())->nextId($tokens, 0, T_STRING));
    }

    public function testNextIdReturnsNullWhenTokenIdIsMissing(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php final class Sample {}', TOKEN_PARSE));

        self::assertNull((new PhpTokenNavigator())->nextId($tokens, 0, T_DOC_COMMENT));
    }

    public function testMatchingBraceReturnsClosingBraceForNestedBlocks(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php if (true) { while (false) {} }', TOKEN_PARSE));

        self::assertSame(18, (new PhpTokenNavigator())->matchingBrace($tokens, 7));
    }

    public function testMatchingBraceReturnsNullWhenClosingBraceIsMissing(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php if (true) {'));

        self::assertNull((new PhpTokenNavigator())->matchingBrace($tokens, 7));
    }
}
