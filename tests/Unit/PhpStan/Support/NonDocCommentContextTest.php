<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Support;

use PhpAiToolkit\PhpStan\Support\NonDocCommentArrayContext;
use PhpAiToolkit\PhpStan\Support\NonDocCommentCatchContext;
use PhpAiToolkit\PhpStan\Support\NonDocCommentContext;
use PhpAiToolkit\PhpStan\Support\NonDocCommentTokenClassifier;
use PhpAiToolkit\PhpStan\Support\ShortArrayOpeningPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
