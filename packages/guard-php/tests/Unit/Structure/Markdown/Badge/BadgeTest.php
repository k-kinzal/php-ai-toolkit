<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Markdown\Badge;

use Guard\Structure\Markdown\Badge\Badge;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Markdown\Badge\Badge
 */
#[CoversClass(Badge::class)]
final class BadgeTest extends TestCase
{
    public function testGetExposesLabelImageLinkAndLine(): void
    {
        $badge = new Badge('License', 'https://img.shields.io/badge/license-MIT-blue', 'LICENSE', 4);

        self::assertSame('License', $badge->label);
        self::assertSame('https://img.shields.io/badge/license-MIT-blue', $badge->image);
        self::assertSame('LICENSE', $badge->link);
        self::assertSame(4, $badge->line);
    }
}
