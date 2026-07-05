<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\CommentTextFormatter;
use PhpAiToolkit\PhpStan\Rule\NonDocCommentErrorBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
