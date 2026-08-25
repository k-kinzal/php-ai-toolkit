<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cache;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Parse\FileSymbols;
use Toolkit\DocGen\Analysis\Reference\Usage;
use Toolkit\DocGen\Cache\CacheStore;
use Toolkit\DocGen\Cache\ParseCache;

/**
 * @covers \Toolkit\DocGen\Cache\ParseCache
 * @uses \Toolkit\DocGen\Cache\CacheStore
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbols
 * @uses \Toolkit\DocGen\Analysis\Reference\Usage
 */
#[CoversClass(ParseCache::class)]
#[UsesClass(CacheStore::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(Usage::class)]
final class ParseCacheTest extends TestCase
{
    public function testRememberAndFindReturnTheSymbolsOfAFile(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-parse-cache-' . bin2hex(random_bytes(4));
        $doc = new ClassLikeDoc('Demo\Alpha', 'Alpha', 'Demo', 'class', 'demo/app', 'src/Alpha.php', 1, 4, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $usage = new Usage('Demo\Beta', null, 'type', 'Demo\Alpha', 'run', 'src/Alpha.php', 7, false);
        $cache = new ParseCache($directory);

        $cache->remember('abcdef', new FileSymbols([$doc], []), [$usage]);
        $found = (new ParseCache($directory))->find('abcdef');

        self::assertIsArray($found);
        self::assertInstanceOf(FileSymbols::class, $found['symbols']);
        self::assertSame('Demo\Alpha', $found['symbols']->classLikes[0]->fqcn);
        self::assertEquals([$usage], $found['usages']);
    }

    public function testFindReturnsNullForAFileNobodyRemembered(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-parse-cache-' . bin2hex(random_bytes(4));

        self::assertNull((new ParseCache($directory))->find('abcdef'));
    }

    public function testPathSpreadsEntriesOverShardDirectories(): void
    {
        $cache = new ParseCache('/tmp/docgen-cache');

        self::assertSame('/tmp/docgen-cache/sources/ab/abcdef.cache', $cache->path('abcdef'));
    }

    public function testCountedSeparatesWhatWasReadBackFromWhatWasParsed(): void
    {
        $cache = new ParseCache(sys_get_temp_dir() . '/docgen-parse-cache-' . bin2hex(random_bytes(4)));

        $cache->counted(true);
        $cache->counted(true);
        $cache->counted(false);

        self::assertSame(2, $cache->reused());
        self::assertSame(1, $cache->parsed());
    }

    public function testEntryRejectsAnythingThatIsNotAWholeParseResult(): void
    {
        $cache = new ParseCache('/tmp/docgen-cache');
        $symbols = new FileSymbols([], []);

        self::assertSame(['symbols' => 'a warning', 'usages' => []], $cache->entry(['symbols' => 'a warning', 'usages' => []]));
        self::assertNull($cache->entry([]));
        self::assertNull($cache->entry(['symbols' => $symbols]));
        self::assertNull($cache->entry(['symbols' => 42, 'usages' => []]));
        self::assertNull($cache->entry(['symbols' => $symbols, 'usages' => 'none']));
        self::assertNull($cache->entry(['symbols' => $symbols, 'usages' => ['not a usage']]));
    }

    public function testKeepMarksAnOldEntryAsReadAndLeavesAFreshOneAlone(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-parse-cache-' . bin2hex(random_bytes(4));
        $cache = new ParseCache($directory);
        $cache->remember('abcdef', new FileSymbols([], []), []);
        $path = $cache->path('abcdef');
        $fresh = (int) filemtime($path);
        touch($path, time() - ParseCache::TOUCH_AFTER - 60);

        $cache->keep($path);
        $kept = (int) filemtime($path);
        $cache->keep($path);

        self::assertGreaterThan(time() - 60, $kept);
        self::assertSame($kept, (int) filemtime($path));
        self::assertGreaterThan(0, $fresh);
    }

    public function testPruneDropsTheEntriesNoRunReadForALongTime(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-parse-cache-' . bin2hex(random_bytes(4));
        $cache = new ParseCache($directory);
        $cache->remember('abcdef', new FileSymbols([], []), []);
        $cache->remember('bcdefa', new FileSymbols([], []), []);
        touch($cache->path('abcdef'), time() - ParseCache::RETENTION - 60);

        $cache->prune();

        self::assertFileDoesNotExist($cache->path('abcdef'));
        self::assertFileExists($cache->path('bcdefa'));
    }

    public function testReusedCountsTheFilesThisRunTookFromTheCache(): void
    {
        $cache = new ParseCache(sys_get_temp_dir() . '/docgen-parse-cache-' . bin2hex(random_bytes(4)));
        $cache->counted(true);

        self::assertSame(1, $cache->reused());
    }

    public function testParsedCountsTheFilesThisRunHadToParse(): void
    {
        $cache = new ParseCache(sys_get_temp_dir() . '/docgen-parse-cache-' . bin2hex(random_bytes(4)));
        $cache->counted(false);

        self::assertSame(1, $cache->parsed());
    }

    public function testPruneShardIgnoresAShardThatIsNotThere(): void
    {
        $cache = new ParseCache(sys_get_temp_dir() . '/docgen-parse-cache-' . bin2hex(random_bytes(4)));

        $cache->pruneShard(sys_get_temp_dir() . '/docgen-missing-' . bin2hex(random_bytes(4)), time());

        self::assertSame(0, $cache->parsed());
    }

    public function testPruneIgnoresACacheDirectoryThatIsNotThere(): void
    {
        $cache = new ParseCache(sys_get_temp_dir() . '/docgen-parse-cache-' . bin2hex(random_bytes(4)));

        $cache->prune();

        self::assertSame(0, $cache->reused());
    }
}
