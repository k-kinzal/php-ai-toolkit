<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Filesystem;

use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListingReader;
use PhpAiToolkit\TreeGuard\TreeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DirectoryListingReader::class)]
#[UsesClass(TreeGuardException::class)]
final class DirectoryListingReaderTest extends TestCase
{
    public function testReadReturnsSortedFilesAndDirs(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-listing-' . bin2hex(random_bytes(4));
        mkdir($dir);
        touch($dir . '/b.txt');
        touch($dir . '/a.txt');
        mkdir($dir . '/Sub');

        $entries = (new DirectoryListingReader())->read($dir);

        self::assertSame(['a.txt', 'b.txt'], $entries['files']);
        self::assertSame(['Sub'], $entries['dirs']);
    }

    public function testReadRejectsUnreadablePath(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Failed to read directory');

        (new DirectoryListingReader())->read(sys_get_temp_dir() . '/treeguard-missing-' . bin2hex(random_bytes(4)));
    }
}
