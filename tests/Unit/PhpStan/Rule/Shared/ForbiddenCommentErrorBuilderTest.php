<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PhpAiToolkit\PhpStan\Rule\Shared\CommentTextFormatter;
use PhpAiToolkit\PhpStan\Rule\Shared\ForbiddenCommentErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\Shared\ForbiddenCommentPattern;
use PHPStan\Rules\LineRuleError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\Shared\ForbiddenCommentErrorBuilder
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\CommentTextFormatter
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\ForbiddenCommentPattern
 */
#[CoversClass(ForbiddenCommentErrorBuilder::class)]
#[UsesClass(CommentTextFormatter::class)]
#[UsesClass(ForbiddenCommentPattern::class)]
final class ForbiddenCommentErrorBuilderTest extends TestCase
{
    public function testPhpstanIgnoreBuildsIdentifierRuleError(): void
    {
        $error = (new ForbiddenCommentErrorBuilder())->phpstanIgnore('// @phpstan-ignore-line', 5);

        self::assertSame('customRules.phpstanIgnoreComment', $error->getIdentifier());
        self::assertInstanceOf(LineRuleError::class, $error);
        self::assertSame(6, $error->getLine());
    }

    public function testInfectionIgnoreAllBuildsIdentifierRuleError(): void
    {
        $error = (new ForbiddenCommentErrorBuilder())->infectionIgnoreAll('/** @infection-ignore-all */', 5);

        self::assertSame('customRules.infectionIgnoreAllComment', $error->getIdentifier());
    }
}
