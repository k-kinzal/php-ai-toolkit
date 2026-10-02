<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Support;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Support\NonDocCommentTokenClassifier;

/**
 * @covers \Toolkit\PhpStan\Support\NonDocCommentTokenClassifier
 */
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
