<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocErrorBuilder;
use Toolkit\PhpStan\Rule\Shared\CommentTextFormatter;

/**
 * @covers \Toolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\Shared\CommentTextFormatter
 */
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
