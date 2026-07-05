<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Support;

use PhpAiToolkit\PhpStan\Support\NonDocCommentTokenClassifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NonDocCommentTokenClassifier::class)]
final class NonDocCommentTokenClassifierTest extends TestCase
{
    public function testIsSignificantRejectsTriviaTokens(): void
    {
        self::assertFalse((new NonDocCommentTokenClassifier())->isSignificant(T_WHITESPACE));
        self::assertTrue((new NonDocCommentTokenClassifier())->isSignificant(T_STRING));
    }

    public function testCanEndExpressionReturnsTrueForExpressionTokens(): void
    {
        self::assertTrue((new NonDocCommentTokenClassifier())->canEndExpression(T_VARIABLE));
        self::assertFalse((new NonDocCommentTokenClassifier())->canEndExpression(T_IF));
    }
}
