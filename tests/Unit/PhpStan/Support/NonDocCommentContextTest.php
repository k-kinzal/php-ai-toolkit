<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Support;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Support\NonDocCommentArrayContext;
use Toolkit\PhpStan\Support\NonDocCommentCatchContext;
use Toolkit\PhpStan\Support\NonDocCommentContext;
use Toolkit\PhpStan\Support\NonDocCommentTokenClassifier;
use Toolkit\PhpStan\Support\ShortArrayOpeningPolicy;

/**
 * @covers \Toolkit\PhpStan\Support\NonDocCommentContext
 * @uses \Toolkit\PhpStan\Support\NonDocCommentArrayContext
 * @uses \Toolkit\PhpStan\Support\NonDocCommentCatchContext
 * @uses \Toolkit\PhpStan\Support\NonDocCommentTokenClassifier
 * @uses \Toolkit\PhpStan\Support\ShortArrayOpeningPolicy
 * @medium
 */
#[CoversClass(NonDocCommentContext::class)]
#[UsesClass(NonDocCommentArrayContext::class)]
#[UsesClass(NonDocCommentCatchContext::class)]
#[UsesClass(NonDocCommentTokenClassifier::class)]
#[UsesClass(ShortArrayOpeningPolicy::class)]
#[Medium]
final class NonDocCommentContextTest extends TestCase
{
    public function testRegisterTokenAllowsLineCommentInsideCatchBodyOnly(): void
    {
        $context = new NonDocCommentContext();

        $context->registerToken(T_CATCH, 'catch');
        $context->registerStringToken('(');
        $context->registerToken(T_STRING, 'RuntimeException');
        $context->registerStringToken(')');
        $context->registerStringToken('{');

        self::assertTrue($context->allowsLineComment());

        $context->registerStringToken('}');

        self::assertFalse($context->allowsLineComment());
    }

    public function testRegisterStringTokenAllowsLineCommentInsideShortArrayLiteral(): void
    {
        $context = new NonDocCommentContext();

        $context->registerStringToken('=');
        $context->registerStringToken('[');

        self::assertTrue($context->allowsLineComment());

        $context->registerStringToken(']');

        self::assertFalse($context->allowsLineComment());
    }

    public function testAllowsLineCommentInsideLongArrayLiteral(): void
    {
        $context = new NonDocCommentContext();

        $context->registerToken(T_ARRAY, 'array');
        $context->registerStringToken('(');

        self::assertTrue($context->allowsLineComment());

        $context->registerStringToken(')');

        self::assertFalse($context->allowsLineComment());
    }

    public function testDoesNotAllowLineCommentInsideArrayAccess(): void
    {
        $context = new NonDocCommentContext();
        $qualifiedConstantContext = new NonDocCommentContext();

        $context->registerToken(T_VARIABLE, '$items');
        $context->registerStringToken('[');
        $qualifiedConstantContext->registerToken(T_NAME_QUALIFIED, 'Foo\BAR');
        $qualifiedConstantContext->registerStringToken('[');

        self::assertFalse($context->allowsLineComment());
        self::assertFalse($qualifiedConstantContext->allowsLineComment());
    }
}
