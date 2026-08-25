<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PHPStan\Rules\LineRuleError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Shared\CommentTextFormatter;
use Toolkit\PhpStan\Rule\Shared\ForbiddenCommentErrorBuilder;
use Toolkit\PhpStan\Rule\Shared\ForbiddenCommentPattern;

/**
 * @covers \Toolkit\PhpStan\Rule\Shared\ForbiddenCommentErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\Shared\CommentTextFormatter
 * @uses \Toolkit\PhpStan\Rule\Shared\ForbiddenCommentPattern
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
