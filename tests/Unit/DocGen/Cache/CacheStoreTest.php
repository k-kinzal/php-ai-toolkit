<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cache;

use PhpAiToolkit\DocGen\Cache\CacheStore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;

#[CoversClass(CacheStore::class)]
final class CacheStoreTest extends TestCase
{
    public function testWriteAndReadReturnWhatWasStored(): void
    {
        $path = sys_get_temp_dir() . '/docgen-store-' . bin2hex(random_bytes(4)) . '/nested/entry.cache';
        $store = new CacheStore();

        self::assertTrue($store->write($path, ['entries' => ['a' => 1]]));
        self::assertSame(['entries' => ['a' => 1]], $store->read($path));
    }

    public function testWriteReplacesAnExistingEntryWithoutLeavingATemporaryFile(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-store-' . bin2hex(random_bytes(4));
        $store = new CacheStore();

        $store->write($directory . '/entry.cache', ['first' => true]);
        $store->write($directory . '/entry.cache', ['second' => true]);

        self::assertSame(['second' => true], $store->read($directory . '/entry.cache'));
        self::assertSame(['entry.cache'], array_values(array_diff((array) scandir($directory), ['.', '..'])));
    }

    public function testReadTreatsAMissingOrUnreadableFileAsAnEmptyCache(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-store-' . bin2hex(random_bytes(4));
        mkdir($directory, 0777, true);
        file_put_contents($directory . '/broken.cache', 'not serialized data');
        file_put_contents($directory . '/scalar.cache', serialize('a string'));
        $store = new CacheStore();

        self::assertSame([], $store->read($directory . '/missing.cache'));
        self::assertSame([], $store->read($directory . '/broken.cache'));
        self::assertSame([], $store->read($directory . '/scalar.cache'));
    }

    #[WithoutErrorHandler]
    public function testWriteReportsThatItCouldNotWrite(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-store-' . bin2hex(random_bytes(4));
        mkdir($directory, 0777, true);
        file_put_contents($directory . '/blocker', '');

        self::assertFalse((new CacheStore())->write($directory . '/blocker/entry.cache', ['a' => 1]));
    }

    #[WithoutErrorHandler]
    public function testPrepareCreatesTheDirectoryAndReportsAFileInItsPlace(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-store-' . bin2hex(random_bytes(4));
        $store = new CacheStore();

        self::assertTrue($store->prepare($directory . '/cache'));
        self::assertDirectoryExists($directory . '/cache');

        file_put_contents($directory . '/file', '');

        self::assertFalse($store->prepare($directory . '/file'));
    }

    public function testClearRemovesTheDirectoryAndEverythingBelowIt(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-store-' . bin2hex(random_bytes(4));
        $store = new CacheStore();
        $store->write($directory . '/sources/ab/entry.cache', ['a' => 1]);
        $store->write($directory . '/pages.cache', ['b' => 2]);

        $store->clear($directory);
        $store->clear($directory);

        self::assertDirectoryDoesNotExist($directory);
    }
}
