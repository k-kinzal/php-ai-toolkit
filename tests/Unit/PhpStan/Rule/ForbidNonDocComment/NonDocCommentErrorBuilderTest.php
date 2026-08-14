<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ForbidNonDocComment;

use PhpAiToolkit\PhpStan\Rule\ForbidNonDocComment\NonDocCommentErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\Shared\CommentTextFormatter;
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
