<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\ForbiddenCommentPattern;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ForbiddenCommentPattern::class)]
final class ForbiddenCommentPatternTest extends TestCase
{
    public function testIsPhpstanIgnoreDetectsPhpstanIgnoreDirective(): void
    {
        self::assertTrue((new ForbiddenCommentPattern())->isPhpstanIgnore('// @phpstan-ignore argument.type'));
    }

    public function testIsInfectionIgnoreAllDetectsInfectionDirective(): void
    {
        self::assertTrue((new ForbiddenCommentPattern())->isInfectionIgnoreAll('/** @infection-ignore-all */'));
    }

    public function testIsHandledReturnsTrueForKnownForbiddenComments(): void
    {
        self::assertTrue((new ForbiddenCommentPattern())->isHandled('/** @phpstan-ignore-next-line */'));
    }

    public function testReportedLineMovesPhpstanIgnoreLineToNextLine(): void
    {
        self::assertSame(6, (new ForbiddenCommentPattern())->reportedLine('// @phpstan-ignore-line', 5));
    }
}
