<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\PhpDoc\NonDocCommentErrorBuilder;
use Toolkit\PhpStan\Rule\Shared\CommentTextFormatter;

/**
 * @covers \Toolkit\PhpStan\Rule\PhpDoc\NonDocCommentErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\Shared\CommentTextFormatter
 */
#[CoversClass(NonDocCommentErrorBuilder::class)]
#[UsesClass(CommentTextFormatter::class)]
final class NonDocCommentErrorBuilderTest extends TestCase
{
    public function testErrorBuildsNonDocCommentError(): void
    {
        $error = (new NonDocCommentErrorBuilder())->error('// comment', 5);

        self::assertSame('customRules.nonDocComment', $error->getIdentifier());
    }
}
