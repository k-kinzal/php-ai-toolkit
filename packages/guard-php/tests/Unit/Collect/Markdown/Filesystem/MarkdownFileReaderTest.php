<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Markdown\Filesystem;

use Guard\Collect\Markdown\Filesystem\MarkdownFileReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Markdown\Filesystem\MarkdownFileReader
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(MarkdownFileReader::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class MarkdownFileReaderTest extends TestCase
{
    public function testReadReturnsFileContents(): void
    {
        $path = sys_get_temp_dir() . '/docguard-read-' . uniqid('', true) . '.md';
        file_put_contents($path, "# Title\n");

        self::assertSame("# Title\n", (new MarkdownFileReader())->read($path));
    }

    public function testReadRejectsDirectories(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Cannot read Markdown document: ' . sys_get_temp_dir());

        (new MarkdownFileReader())->read(sys_get_temp_dir());
    }
}
