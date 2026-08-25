<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\PhpDoc\NonDocCommentErrorBuilder;
use Toolkit\PhpStan\Rule\PhpDoc\NonDocCommentTokenAnalyzer;
use Toolkit\PhpStan\Rule\Shared\CommentTextFormatter;
use Toolkit\PhpStan\Rule\Shared\ForbiddenCommentPattern;
use Toolkit\PhpStan\Support\NonDocCommentArrayContext;
use Toolkit\PhpStan\Support\NonDocCommentCatchContext;
use Toolkit\PhpStan\Support\NonDocCommentContext;
use Toolkit\PhpStan\Support\NonDocCommentTokenClassifier;
use Toolkit\PhpStan\Support\ShortArrayOpeningPolicy;

/**
 * @covers \Toolkit\PhpStan\Rule\PhpDoc\NonDocCommentTokenAnalyzer
 * @uses \Toolkit\PhpStan\Rule\Shared\CommentTextFormatter
 * @uses \Toolkit\PhpStan\Rule\Shared\ForbiddenCommentPattern
 * @uses \Toolkit\PhpStan\Support\NonDocCommentArrayContext
 * @uses \Toolkit\PhpStan\Support\NonDocCommentCatchContext
 * @uses \Toolkit\PhpStan\Support\NonDocCommentContext
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\NonDocCommentErrorBuilder
 * @uses \Toolkit\PhpStan\Support\NonDocCommentTokenClassifier
 * @uses \Toolkit\PhpStan\Support\ShortArrayOpeningPolicy
 */
#[CoversClass(NonDocCommentTokenAnalyzer::class)]
#[UsesClass(CommentTextFormatter::class)]
#[UsesClass(ForbiddenCommentPattern::class)]
#[UsesClass(NonDocCommentArrayContext::class)]
#[UsesClass(NonDocCommentCatchContext::class)]
#[UsesClass(NonDocCommentContext::class)]
#[UsesClass(NonDocCommentErrorBuilder::class)]
#[UsesClass(NonDocCommentTokenClassifier::class)]
#[UsesClass(ShortArrayOpeningPolicy::class)]
final class NonDocCommentTokenAnalyzerTest extends TestCase
{
    public function testErrorsReturnsNonDocCommentErrors(): void
    {
        $errors = (new NonDocCommentTokenAnalyzer())->errors([
            [T_COMMENT, '// comment', 5],
        ]);

        self::assertCount(1, $errors);
    }
}
