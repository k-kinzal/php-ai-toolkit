<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Markdown\Badge;

use Guard\Structure\Markdown\Badge\Badge;
use Guard\Structure\Markdown\Badge\BadgeBlock;
use Guard\Structure\Markdown\Heading;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Markdown\Badge\BadgeBlock
 * @uses \Guard\Structure\Markdown\Badge\Badge
 * @uses \Guard\Structure\Markdown\Heading
 */
#[CoversClass(BadgeBlock::class)]
#[UsesClass(Badge::class)]
#[UsesClass(Heading::class)]
final class BadgeBlockTest extends TestCase
{
    public function testGetExposesTitleBadgesAndEarlyLine(): void
    {
        $title = new Heading(1, 'Tool', 2);
        $badges = [new Badge('PHP', 'https://img.shields.io/badge/php-8.1', 'https://www.php.net/', 4)];
        $block = new BadgeBlock($title, $badges, 1);

        self::assertSame($title, $block->title);
        self::assertSame($badges, $block->badges);
        self::assertSame(1, $block->earlyLine);
    }
}
