<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PhpAiToolkit\PhpStan\Rule\Shared\CommentTextFormatter;
use PhpAiToolkit\PhpStan\Rule\Shared\ForbiddenCommentErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\Shared\ForbiddenCommentPattern;
use PhpAiToolkit\PhpStan\Rule\Shared\ForbiddenCommentTokenAnalyzer;
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
