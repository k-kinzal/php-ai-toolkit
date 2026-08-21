<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use PhpAiToolkit\PhpStan\Rule\PhpDoc\NonDocCommentErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\PhpDoc\NonDocCommentTokenAnalyzer;
use PhpAiToolkit\PhpStan\Rule\Shared\CommentTextFormatter;
use PhpAiToolkit\PhpStan\Rule\Shared\ForbiddenCommentPattern;
use PhpAiToolkit\PhpStan\Support\NonDocCommentArrayContext;
use PhpAiToolkit\PhpStan\Support\NonDocCommentCatchContext;
use PhpAiToolkit\PhpStan\Support\NonDocCommentContext;
use PhpAiToolkit\PhpStan\Support\NonDocCommentTokenClassifier;
use PhpAiToolkit\PhpStan\Support\ShortArrayOpeningPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
