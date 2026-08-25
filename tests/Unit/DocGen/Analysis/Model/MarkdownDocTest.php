<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Model;

use PhpAiToolkit\DocGen\Analysis\Model\MarkdownDoc;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Model\MarkdownDoc
 */
#[CoversClass(MarkdownDoc::class)]
final class MarkdownDocTest extends TestCase
{
    public function testStoresDocumentLocationAndTitle(): void
    {
        $document = new MarkdownDoc('demo/pkg', 'docs/guide.md', 'packages/demo/docs/guide.md', 'Guide');

        self::assertSame('demo/pkg', $document->packageName);
        self::assertSame('docs/guide.md', $document->path);
        self::assertSame('packages/demo/docs/guide.md', $document->file);
        self::assertSame('Guide', $document->title);
    }
}
