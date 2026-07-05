<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\CommentTextFormatter;
use PhpAiToolkit\PhpStan\Rule\ForbiddenCommentErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\ForbiddenCommentPattern;
use PhpAiToolkit\PhpStan\Rule\ForbiddenCommentTokenAnalyzer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ForbiddenCommentTokenAnalyzer::class)]
#[UsesClass(CommentTextFormatter::class)]
#[UsesClass(ForbiddenCommentErrorBuilder::class)]
#[UsesClass(ForbiddenCommentPattern::class)]
final class ForbiddenCommentTokenAnalyzerTest extends TestCase
{
    public function testErrorsReturnsSuppressionCommentErrors(): void
    {
        $errors = (new ForbiddenCommentTokenAnalyzer())->errors([
            [T_COMMENT, '// @phpstan-ignore argument.type', 5],
            [T_DOC_COMMENT, '/** @infection-ignore-all */', 6],
        ]);

        self::assertCount(2, $errors);
    }
}
