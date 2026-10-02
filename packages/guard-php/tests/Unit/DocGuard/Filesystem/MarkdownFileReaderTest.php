<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Filesystem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\DocGuardException;
use Toolkit\DocGuard\Filesystem\MarkdownFileReader;

/**
 * @covers \Toolkit\DocGuard\Filesystem\MarkdownFileReader
 * @uses \Toolkit\DocGuard\DocGuardException
 */
#[CoversClass(MarkdownFileReader::class)]
#[UsesClass(DocGuardException::class)]
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
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('Cannot read Markdown document: ' . sys_get_temp_dir());

        (new MarkdownFileReader())->read(sys_get_temp_dir());
    }
}
