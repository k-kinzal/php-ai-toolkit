<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Config\DeclaredHeading;
use Toolkit\DocGuard\Config\DocumentConfig;

/**
 * @covers \Toolkit\DocGuard\Config\DocumentConfig
 * @uses \Toolkit\DocGuard\Config\DeclaredHeading
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
