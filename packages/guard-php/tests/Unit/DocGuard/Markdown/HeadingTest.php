<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Markdown;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Markdown\Heading;

/**
 * @covers \Toolkit\DocGuard\Markdown\Heading
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
