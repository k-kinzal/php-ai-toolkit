<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Shared\CommentTextFormatter;
use Toolkit\PhpStan\Rule\Shared\ForbiddenCommentErrorBuilder;
use Toolkit\PhpStan\Rule\Shared\ForbiddenCommentPattern;
use Toolkit\PhpStan\Rule\Shared\ForbiddenCommentTokenAnalyzer;

/**
 * @covers \Toolkit\PhpStan\Rule\Shared\ForbiddenCommentTokenAnalyzer
 * @uses \Toolkit\PhpStan\Rule\Shared\CommentTextFormatter
 * @uses \Toolkit\PhpStan\Rule\Shared\ForbiddenCommentErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\Shared\ForbiddenCommentPattern
 */
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
