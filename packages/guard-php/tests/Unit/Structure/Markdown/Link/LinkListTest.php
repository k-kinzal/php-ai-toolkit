<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Markdown\Link;

use Guard\Structure\Markdown\Link\LinkList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Markdown\Link\LinkList

 */
#[CoversClass(LinkList::class)]

final class LinkListTest extends TestCase
{
    public function testPathsDeduplicatesTargetsWithoutLosingOccurrences(): void
    {
        $links = new LinkList([
            ['url' => 'a.md#one', 'path' => 'a.md', 'line' => 1],
            ['url' => 'a.md#two', 'path' => 'a.md', 'line' => 2],
        ]);
        self::assertSame(['a.md'], $links->paths());
    }

    public function testAllPreservesSeparateLinksToTheSameFile(): void
    {
        $links = new LinkList([
            ['url' => 'a.md#one', 'path' => 'a.md', 'line' => 1],
            ['url' => 'a.md#two', 'path' => 'a.md', 'line' => 2],
        ]);
        self::assertCount(2, $links->all());
        self::assertSame(2, $links->all()[1]['line']);
    }

}
