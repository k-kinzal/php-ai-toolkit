<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Markdown\Badge;

use Guard\Structure\Markdown\Badge\Badge;
use Guard\Structure\Markdown\Badge\BadgeLineParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Markdown\Badge\BadgeLineParser
 * @uses \Guard\Structure\Markdown\Badge\Badge
 */
#[CoversClass(BadgeLineParser::class)]
#[UsesClass(Badge::class)]
final class BadgeLineParserTest extends TestCase
{
    public function testParseReadsEveryBadgeOfABadgeOnlyLine(): void
    {
        $badges = (new BadgeLineParser())->parse('[![PHP](https://img.shields.io/badge/php-8.1-777bb4)](https://www.php.net/) [![License](https://img.shields.io/badge/license-MIT-blue "MIT")](LICENSE)', 3);

        self::assertNotNull($badges);
        self::assertCount(2, $badges);
        self::assertSame('PHP', $badges[0]->label);
        self::assertSame('https://img.shields.io/badge/php-8.1-777bb4', $badges[0]->image);
        self::assertSame('https://www.php.net/', $badges[0]->link);
        self::assertSame('License', $badges[1]->label);
        self::assertSame('LICENSE', $badges[1]->link);
        self::assertSame(3, $badges[1]->line);
    }

    public function testParseRejectsALineWithOtherContent(): void
    {
        self::assertNull((new BadgeLineParser())->parse('[![PHP](https://img.shields.io/badge/php-8.1)](https://www.php.net/) supported', 1));
        self::assertNull((new BadgeLineParser())->parse('![PHP](https://img.shields.io/badge/php-8.1)', 1));
        self::assertNull((new BadgeLineParser())->parse('', 1));
    }
}
