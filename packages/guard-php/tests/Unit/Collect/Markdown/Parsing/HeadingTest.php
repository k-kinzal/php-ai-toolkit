<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Markdown\Parsing;

use Guard\Collect\Markdown\Parsing\Heading;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Markdown\Parsing\Heading
 */
#[CoversClass(Heading::class)]
final class HeadingTest extends TestCase
{
    public function testGetExposesLevelTextAndLine(): void
    {
        $heading = new Heading(2, 'Usage', 7);

        self::assertSame(2, $heading->level);
        self::assertSame('Usage', $heading->text);
        self::assertSame(7, $heading->line);
    }

    public function testNotationRendersAtxHeading(): void
    {
        self::assertSame('## Usage', (new Heading(2, 'Usage', 1))->notation());
        self::assertSame('###', (new Heading(3, '', 1))->notation());
    }
}
