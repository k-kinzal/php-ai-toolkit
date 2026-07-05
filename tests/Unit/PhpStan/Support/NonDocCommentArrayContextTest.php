<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Support;

use PhpAiToolkit\PhpStan\Support\NonDocCommentArrayContext;
use PhpAiToolkit\PhpStan\Support\NonDocCommentTokenClassifier;
use PhpAiToolkit\PhpStan\Support\ShortArrayOpeningPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NonDocCommentArrayContext::class)]
#[UsesClass(NonDocCommentTokenClassifier::class)]
#[UsesClass(ShortArrayOpeningPolicy::class)]
final class NonDocCommentArrayContextTest extends TestCase
{
    public function testRegisterArrayTokenAllowsLineCommentInLongArray(): void
    {
        $context = new NonDocCommentArrayContext();
        $context->registerArrayToken('array');
        $context->registerStringToken('(');

        self::assertTrue($context->allowsLineComment());
    }

    public function testRegisterTokenPreventsShortArrayAfterExpressionToken(): void
    {
        $context = new NonDocCommentArrayContext();
        $context->registerToken(T_VARIABLE, '$items');
        $context->registerStringToken('[');

        self::assertFalse($context->allowsLineComment());
    }

    public function testRegisterStringTokenClosesShortArray(): void
    {
        $context = new NonDocCommentArrayContext();
        $context->registerStringToken('=');
        $context->registerStringToken('[');
        $context->registerStringToken(']');

        self::assertFalse($context->allowsLineComment());
    }

    public function testRegisterOpeningBracketAllowsLineCommentInShortArray(): void
    {
        $context = new NonDocCommentArrayContext();
        $context->registerOpeningBracket();

        self::assertTrue($context->allowsLineComment());
    }

    public function testRegisterClosingBracketClosesShortArray(): void
    {
        $context = new NonDocCommentArrayContext();
        $context->registerOpeningBracket();
        $context->registerClosingBracket();

        self::assertFalse($context->allowsLineComment());
    }

    public function testRegisterOpeningParenthesisAllowsLineCommentInLongArray(): void
    {
        $context = new NonDocCommentArrayContext();
        $context->registerArrayToken('array');
        $context->registerOpeningParenthesis();

        self::assertTrue($context->allowsLineComment());
    }

    public function testRegisterClosingParenthesisClosesLongArray(): void
    {
        $context = new NonDocCommentArrayContext();
        $context->registerArrayToken('array');
        $context->registerOpeningParenthesis();
        $context->registerClosingParenthesis();

        self::assertFalse($context->allowsLineComment());
    }

    public function testRegisterOtherStringTokenClearsPendingLongArray(): void
    {
        $context = new NonDocCommentArrayContext();
        $context->registerArrayToken('array');
        $context->registerOtherStringToken('{');
        $context->registerOpeningParenthesis();

        self::assertFalse($context->allowsLineComment());
    }

    public function testAllowsLineCommentReturnsFalseOutsideArray(): void
    {
        self::assertFalse((new NonDocCommentArrayContext())->allowsLineComment());
    }
}
