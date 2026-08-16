<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cache;

use PhpAiToolkit\DocGen\Analysis\Parse\FileSymbols;
use PhpAiToolkit\DocGen\Cache\CacheStore;
use PhpAiToolkit\DocGen\Cache\GenerationCache;
use PhpAiToolkit\DocGen\Cache\PageRecord;
use PhpAiToolkit\DocGen\Cache\ParseCache;
use PhpAiToolkit\DocGen\Cache\RenderCache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenerationCache::class)]
#[UsesClass(CacheStore::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(PageRecord::class)]
#[UsesClass(ParseCache::class)]
#[UsesClass(RenderCache::class)]
final class GenerationCacheTest extends TestCase
{
    public function testGetExposesBothHalvesOfTheCache(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-generation-cache-' . bin2hex(random_bytes(4));
        $sources = new ParseCache($directory);
        $pages = new RenderCache($directory, $directory . '/site');
        $cache = new GenerationCache($sources, $pages);

        self::assertSame($sources, $cache->sources);
        self::assertSame($pages, $cache->pages);
    }

    public function testARunWithoutACacheHoldsNeitherHalf(): void
    {
        $cache = new GenerationCache();

        $cache->load();
        $cache->save();

        self::assertNull($cache->sources);
        self::assertNull($cache->pages);
        self::assertNull($cache->summary());
    }

    public function testSummaryReportsWhatBothHalvesReusedAndWhatTheyDidNot(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-generation-cache-' . bin2hex(random_bytes(4));
        $sources = new ParseCache($directory);
        $pages = new RenderCache($directory, $directory . '/site');
        $sources->counted(true);
        $sources->counted(false);
        $pages->record($directory . '/site', [new PageRecord('a.html', 'a', 1, true), new PageRecord('b.html', 'b', 1, false)]);

        self::assertSame('Cache: 1 of 2 sources and 1 of 2 pages reused', (new GenerationCache($sources, $pages))->summary());
    }

    public function testLoadReadsThePagesOfThePreviousRun(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-generation-cache-' . bin2hex(random_bytes(4));
        $out = $directory . '/site';
        $pages = new RenderCache($directory, $out);
        $pages->record($out, [new PageRecord('a.html', 'a', 7, true)]);
        $next = new RenderCache($directory, $out);
        $cache = new GenerationCache(new ParseCache($directory), $next);

        $cache->load();

        self::assertSame(7, $next->sizeOf('a.html'));
    }

    public function testSaveDropsTheEntriesNoRunReadForALongTime(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-generation-cache-' . bin2hex(random_bytes(4));
        $sources = new ParseCache($directory);
        $sources->remember('abcdef', new FileSymbols([], []), []);
        touch($sources->path('abcdef'), time() - ParseCache::RETENTION - 60);

        (new GenerationCache($sources))->save();

        self::assertFileDoesNotExist($sources->path('abcdef'));
    }
}
