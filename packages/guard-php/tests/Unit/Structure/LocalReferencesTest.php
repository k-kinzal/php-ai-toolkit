<?php

declare(strict_types=1);

namespace Tests\Unit\Structure;

use Guard\Structure\LocalReferences;
use Guard\Structure\Markdown\Link\LinkList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\LocalReferences
 * @uses \Guard\Structure\Markdown\Link\LinkList
 */
#[CoversClass(LocalReferences::class)]
#[UsesClass(LinkList::class)]
final class LocalReferencesTest extends TestCase
{
    public function testPathsExposesDecodedTargetsIndependentlyOfDisplayUrls(): void
    {
        $references = new LinkList([['url' => 'a%20b.md#part', 'path' => 'a b.md', 'line' => 4]]);
        self::assertSame(['a b.md'], $references->paths());
    }

}
