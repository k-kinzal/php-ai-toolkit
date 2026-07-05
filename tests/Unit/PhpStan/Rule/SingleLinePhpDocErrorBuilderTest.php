<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\CommentTextFormatter;
use PhpAiToolkit\PhpStan\Rule\SingleLinePhpDocErrorBuilder;
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
