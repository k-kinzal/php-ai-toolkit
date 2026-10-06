<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Markdown;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Markdown\MarkdownDocuments
 */
#[CoversClass(\Guard\Collect\Markdown\MarkdownDocuments::class)]
final class MarkdownDocumentsTest extends TestCase
{
    public function testMissingDocumentsAreDifferentFromDocumentsWithoutHeadings(): void
    {
        $subject = new \Guard\Collect\Markdown\MarkdownDocuments(['missing.md' => null, 'empty.md' => []], [], []);
        self::assertNull($subject->headings['missing.md']);
        self::assertSame([], $subject->headings['empty.md']);
    }

}
