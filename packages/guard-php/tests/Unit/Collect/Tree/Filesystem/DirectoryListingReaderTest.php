<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Tree\Filesystem;

use Guard\Collect\Tree\Filesystem\DirectoryListingReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Tree\Filesystem\DirectoryListingReader
 * @uses \Guard\Collect\Tree\Filesystem\DirectoryListing
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(DirectoryListingReader::class)]
#[UsesClass(\Guard\Collect\Tree\Filesystem\DirectoryListing::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class DirectoryListingReaderTest extends TestCase
{
    public function testReadReturnsSortedFilesAndDirs(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-listing-' . uniqid('', true);
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
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Failed to read directory');

        (new DirectoryListingReader())->read(sys_get_temp_dir() . '/treeguard-missing-' . uniqid('', true));
    }
}
