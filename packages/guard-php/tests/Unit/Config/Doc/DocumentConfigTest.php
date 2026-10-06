<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Doc;

use Guard\Config\Doc\DeclaredHeading;
use Guard\Config\Doc\DocumentConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Doc\DocumentConfig
 * @uses \Guard\Config\Doc\DeclaredHeading
 */
#[CoversClass(DocumentConfig::class)]
#[UsesClass(DeclaredHeading::class)]
final class DocumentConfigTest extends TestCase
{
    public function testGetExposesPathHeadingsAndMaxLevel(): void
    {
        $headings = [new DeclaredHeading(1, 'Title')];
        $document = new DocumentConfig('docs/guide.md', $headings, 3);

        self::assertSame('docs/guide.md', $document->path);
        self::assertSame($headings, $document->headings);
        self::assertSame(3, $document->maxLevel);
    }
}
