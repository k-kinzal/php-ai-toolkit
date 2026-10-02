<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Generation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use Toolkit\DocGuard\Generation\ConfigYamlWriter;
use Toolkit\DocGuard\Markdown\Heading;

/**
 * @covers \Toolkit\DocGuard\Generation\ConfigYamlWriter
 * @uses \Toolkit\DocGuard\Markdown\Heading
 */
#[CoversClass(ConfigYamlWriter::class)]
#[UsesClass(Heading::class)]
final class ConfigYamlWriterTest extends TestCase
{
    public function testWriteProducesHumanManagedConfig(): void
    {
        $yaml = (new ConfigYamlWriter())->write(
            ['CLAUDE.md' => [], 'README.md' => [new Heading(1, 'Tool', 1), new Heading(2, "It's: #1", 5)]],
            ['*.md'],
        );

        self::assertStringStartsWith('# NOTE: You do not have permission to overwrite this file. Please ask a human operator to perform the changes for you.' . "\n", $yaml);
        self::assertSame([
            'documents' => [
                'CLAUDE.md' => ['headings' => []],
                'README.md' => ['headings' => ['# Tool', "## It's: #1"]],
            ],
            'scan' => ['*.md'],
            'report' => ['reporter' => 'ai', 'order_by' => ['path', 'line', 'rule']],
        ], Yaml::parse($yaml));
    }

    public function testWriteEmitsEmptyScanList(): void
    {
        $yaml = (new ConfigYamlWriter())->write(['2024' => [new Heading(1, 'Year', 1)]], []);

        self::assertStringContainsString("  '2024':\n", $yaml);
        self::assertStringContainsString("\nscan: []\n", $yaml);
    }

    public function testQuoteEscapesSingleQuotes(): void
    {
        self::assertSame("'## It''s'", (new ConfigYamlWriter())->quote("## It's"));
    }
}
