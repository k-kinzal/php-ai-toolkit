<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Value;

use Guard\Config\Value\DeclaredHeading;
use Guard\Config\Value\DocumentConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Value\DocumentConfig
 * @uses \Guard\Config\Value\DeclaredHeading
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
