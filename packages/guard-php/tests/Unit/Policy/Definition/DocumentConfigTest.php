<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Definition;

use Guard\Policy\Definition\BadgeEntry;
use Guard\Policy\Definition\DeclaredHeading;
use Guard\Policy\Definition\DocumentConfig;
use Guard\Policy\Definition\OutlineEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Definition\DocumentConfig
 * @uses \Guard\Policy\Definition\BadgeEntry
 * @uses \Guard\Policy\Definition\DeclaredHeading
 * @uses \Guard\Policy\Definition\OutlineEntry
 */
#[CoversClass(DocumentConfig::class)]
#[UsesClass(BadgeEntry::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(OutlineEntry::class)]
final class DocumentConfigTest extends TestCase
{
    public function testGetExposesPathHeadingsAndMaxLevel(): void
    {
        $headings = [new DeclaredHeading(1, 'Title')];
        $document = new DocumentConfig('docs/guide.md', $headings, 3);

        self::assertSame('docs/guide.md', $document->path);
        self::assertSame($headings, $document->headings);
        self::assertSame(3, $document->maxLevel);
        self::assertSame([], $document->outlines);
        self::assertNull($document->badges);
        self::assertNull($document->content);
    }

    public function testGetExposesOutlinesBadgesAndContent(): void
    {
        $outlines = ['agents' => [new OutlineEntry(1, 'AGENTS')]];
        $badges = [new BadgeEntry('PHP', 'https://img.shields.io/badge/php-*')];
        $document = new DocumentConfig('AGENTS.md', null, 6, $outlines, $badges, '@AGENTS.md');

        self::assertNull($document->headings);
        self::assertSame($outlines, $document->outlines);
        self::assertSame($badges, $document->badges);
        self::assertSame('@AGENTS.md', $document->content);
    }
}
