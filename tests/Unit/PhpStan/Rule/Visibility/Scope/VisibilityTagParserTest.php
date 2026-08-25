<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Visibility\Scope;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityTagParser;

/**
 * @covers \Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityTagParser
 */
#[CoversClass(VisibilityTagParser::class)]
final class VisibilityTagParserTest extends TestCase
{
    public function testValuesReadTagsButNotProseMentions(): void
    {
        $comment = "/**\n * Mention @visibility public in prose.\n * @visibility namespace\n * @visibility App\\Console\n */";

        self::assertSame(['namespace', 'App\Console'], (new VisibilityTagParser())->values($comment));
    }
}
