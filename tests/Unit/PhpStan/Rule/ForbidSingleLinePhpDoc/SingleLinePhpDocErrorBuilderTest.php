<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ForbidSingleLinePhpDoc;

use PhpAiToolkit\PhpStan\Rule\ForbidSingleLinePhpDoc\SingleLinePhpDocErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\Shared\CommentTextFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SingleLinePhpDocErrorBuilder::class)]
#[UsesClass(CommentTextFormatter::class)]
final class SingleLinePhpDocErrorBuilderTest extends TestCase
{
    public function testErrorBuildsSingleLinePhpDocError(): void
    {
        $error = (new SingleLinePhpDocErrorBuilder())->error('/** doc */', 7);

        self::assertSame('customRules.singleLinePhpDoc', $error->getIdentifier());
    }
}
