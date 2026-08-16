<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cache;

use PhpAiToolkit\DocGen\Cache\CacheStore;
use PhpAiToolkit\DocGen\Cache\PageRecord;
use PhpAiToolkit\DocGen\Cache\RenderCache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RenderCache::class)]
#[UsesClass(CacheStore::class)]
#[UsesClass(PageRecord::class)]
final class RenderCacheTest extends TestCase
{
    public function testIsFreshOnlyWhenTheSignatureAndTheWrittenFileStillMatch(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-render-cache-' . bin2hex(random_bytes(4));
        $out = $directory . '/site';
        mkdir($out, 0777, true);
        file_put_contents($out . '/index.html', '12345');
        $cache = new RenderCache($directory, $out);
        $cache->record($out, [new PageRecord('index.html', 'signature', 5, true)]);
        $next = new RenderCache($directory, $out);
        $next->load();

        self::assertTrue($next->isFresh($out, 'index.html', 'signature'));
        self::assertFalse($next->isFresh($out, 'index.html', 'other'));
        self::assertFalse($next->isFresh($out, 'missing.html', 'signature'));

        file_put_contents($out . '/index.html', 'edited by hand');

        self::assertFalse($next->isFresh($out, 'index.html', 'signature'));
    }

    public function testIsFreshIsFalseWhenTheSiteWasRemoved(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-render-cache-' . bin2hex(random_bytes(4));
        $out = $directory . '/site';
        mkdir($out, 0777, true);
        file_put_contents($out . '/index.html', '12345');
        $cache = new RenderCache($directory, $out);
        $cache->record($out, [new PageRecord('index.html', 'signature', 5, true)]);
        unlink($out . '/index.html');
        $next = new RenderCache($directory, $out);
        $next->load();

        self::assertFalse($next->isFresh($out, 'index.html', 'signature'));
    }

    public function testSizeOfReturnsWhatWasRememberedAboutAPage(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-render-cache-' . bin2hex(random_bytes(4));
        $out = $directory . '/site';
        $cache = new RenderCache($directory, $out);
        $cache->record($out, [new PageRecord('index.html', 'signature', 17, true)]);
        $next = new RenderCache($directory, $out);
        $next->load();

        self::assertSame(17, $next->sizeOf('index.html'));
        self::assertSame(0, $next->sizeOf('missing.html'));
    }

    public function testRecordRemovesThePagesTheSiteNoLongerHas(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-render-cache-' . bin2hex(random_bytes(4));
        $out = $directory . '/site';
        mkdir($out, 0777, true);
        file_put_contents($out . '/kept.html', 'kept');
        file_put_contents($out . '/gone.html', 'gone');
        $cache = new RenderCache($directory, $out);
        $cache->record($out, [new PageRecord('kept.html', 'a', 4, true), new PageRecord('gone.html', 'b', 4, true)]);
        $next = new RenderCache($directory, $out);
        $next->load();

        $next->record($out, [new PageRecord('kept.html', 'a', 4, false)]);

        self::assertFileExists($out . '/kept.html');
        self::assertFileDoesNotExist($out . '/gone.html');
        self::assertSame(1, $next->reused());
        self::assertSame(0, $next->rendered());
    }

    public function testRecordCountsWhatWasRenderedAndWhatWasLeftAlone(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-render-cache-' . bin2hex(random_bytes(4));
        $cache = new RenderCache($directory, $directory . '/site');

        $cache->record($directory . '/site', [
            new PageRecord('a.html', 'a', 1, true),
            new PageRecord('b.html', 'b', 1, false),
            new PageRecord('c.html', 'c', 1, false),
        ]);

        self::assertSame(1, $cache->rendered());
        self::assertSame(2, $cache->reused());
    }

    public function testRenderedCountsThePagesThisRunWrote(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-render-cache-' . bin2hex(random_bytes(4));
        $cache = new RenderCache($directory, $directory . '/site');

        $cache->record($directory . '/site', [new PageRecord('a.html', 'a', 1, true)]);

        self::assertSame(1, $cache->rendered());
    }

    public function testReusedCountsThePagesThisRunLeftAlone(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-render-cache-' . bin2hex(random_bytes(4));
        $cache = new RenderCache($directory, $directory . '/site');

        $cache->record($directory . '/site', [new PageRecord('a.html', 'a', 1, false)]);

        self::assertSame(1, $cache->reused());
    }

    public function testLoadIgnoresACacheFileThatHoldsNoPages(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-render-cache-' . bin2hex(random_bytes(4));
        $out = $directory . '/site';
        $cache = new RenderCache($directory, $out);
        (new CacheStore())->write($directory . '/' . RenderCache::FILE_PREFIX . substr(hash('sha256', $out), 0, 16) . '.cache', ['pages' => 'not a list']);

        $cache->load();

        self::assertSame(0, $cache->sizeOf('index.html'));
    }

    public function testPageRejectsAnythingThatIsNotAWrittenPage(): void
    {
        $cache = new RenderCache('/tmp/docgen-cache', '/tmp/docgen-site');

        self::assertSame(['signature' => 'a', 'size' => 2], $cache->page(['signature' => 'a', 'size' => 2]));
        self::assertNull($cache->page(['signature' => 'a']));
        self::assertNull($cache->page(['signature' => 1, 'size' => 2]));
        self::assertNull($cache->page(['signature' => 'a', 'size' => '2']));
        self::assertNull($cache->page('not a page'));
    }
}
