<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Markdown\Link;

use Guard\Structure\Markdown\Link\LinkList;
use Guard\Structure\Markdown\Link\LinkStructurer;
use Guard\Structure\Source;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Markdown\Link\LinkStructurer
 * @uses \Guard\Structure\Markdown\Link\LinkList
 * @uses \Guard\Structure\Source
 */
#[CoversClass(LinkStructurer::class)]
#[UsesClass(LinkList::class)]
#[UsesClass(Source::class)]
final class LinkStructurerTest extends TestCase
{
    public function testStructureParsesRealLinksAndSkipsCodeAndHtml(): void
    {
        $markdown = <<<'MD'
---
example: '[metadata](absent.md)'
---
[relative](guide/intro.md#start)
![image][picture]
[missing reference][undefined]

[picture]: <assets/a b.png>

`[inline](absent.md)`

```md
[fenced](absent.md)
```

    [indented](absent.md)

<!-- [comment](absent.md) -->
<a href="absent.md">HTML</a>
[web](https://example.com) [email](mailto:a@example.com) [anchor](#title)
[route](/docs) [protocol](//example.com) [encoded](guide/a%20b.md?q=x#title)
MD;
        $links = (new LinkStructurer())->structure(new Source($markdown, []));
        self::assertSame(['guide/intro.md', 'assets/a b.png', 'guide/a b.md'], $links->paths());
        self::assertSame('guide/intro.md#start', $links->all()[0]['url']);
    }

    public function testLocalPathPreservesEncodedFragmentCharacters(): void
    {
        self::assertSame('file#name.md', (new LinkStructurer())->localPath('file%23name.md#section'));
        self::assertNull((new LinkStructurer())->localPath('file%00.md'));
        self::assertNull((new LinkStructurer())->localPath('data:text/plain,hello'));
    }

}
