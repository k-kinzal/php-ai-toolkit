<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Report\Cache;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Cache\CacheStore;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Report\Cache\CachedPageWriter;
use Toolkit\DocGen\Report\Cache\PageRecord;
use Toolkit\DocGen\Report\Cache\RenderCache;
use Toolkit\DocGen\Report\Filesystem\SiteFileWriter;

/**
 * @covers \Toolkit\DocGen\Report\Cache\CachedPageWriter
 * @uses \Toolkit\DocGen\Cache\CacheStore
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Report\Cache\PageRecord
 * @uses \Toolkit\DocGen\Report\Cache\RenderCache
 * @uses \Toolkit\DocGen\Report\Filesystem\SiteFileWriter
 */
#[CoversClass(CachedPageWriter::class)]
#[UsesClass(CacheStore::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(PageRecord::class)]
#[UsesClass(RenderCache::class)]
#[UsesClass(SiteFileWriter::class)]
final class CachedPageWriterTest extends TestCase
{
    public function testWriteRendersAndReportsThePageWhenNothingIsCached(): void
    {
        $out = sys_get_temp_dir() . '/docgen-page-writer-' . bin2hex(random_bytes(4));

        $record = (new CachedPageWriter())->write($out, 'index.html', 'signature', static fn (): string => '<h1>Doc</h1>');

        self::assertSame('index.html', $record->path);
        self::assertSame('signature', $record->signature);
        self::assertSame(12, $record->size);
        self::assertTrue($record->rendered);
        self::assertSame('<h1>Doc</h1>', file_get_contents($out . '/index.html'));
    }

    public function testWriteLeavesAPageAloneWhenTheSiteAlreadyHoldsIt(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-page-writer-' . bin2hex(random_bytes(4));
        $out = $directory . '/site';
        $cache = new RenderCache($directory, $out);
        (new CachedPageWriter(null, $cache))->write($out, 'index.html', 'signature', static fn (): string => '<h1>Doc</h1>');
        $cache->record($out, [new PageRecord('index.html', 'signature', 12, true)]);
        $next = new RenderCache($directory, $out);
        $next->load();

        $record = (new CachedPageWriter(null, $next))->write($out, 'index.html', 'signature', static fn (): string => '<h1>Rendered again</h1>');

        self::assertFalse($record->rendered);
        self::assertSame(12, $record->size);
        self::assertSame('<h1>Doc</h1>', file_get_contents($out . '/index.html'));
    }

    public function testWriteRendersAgainWhenTheSignatureChanged(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-page-writer-' . bin2hex(random_bytes(4));
        $out = $directory . '/site';
        $cache = new RenderCache($directory, $out);
        (new CachedPageWriter(null, $cache))->write($out, 'index.html', 'signature', static fn (): string => '<h1>Doc</h1>');
        $cache->record($out, [new PageRecord('index.html', 'signature', 12, true)]);
        $next = new RenderCache($directory, $out);
        $next->load();

        $record = (new CachedPageWriter(null, $next))->write($out, 'index.html', 'other', static fn (): string => '<h1>New</h1>');

        self::assertTrue($record->rendered);
        self::assertSame('<h1>New</h1>', file_get_contents($out . '/index.html'));
    }

    public function testRecordsCollectsWhatEveryWorkerReported(): void
    {
        $writer = new CachedPageWriter();
        $first = new PageRecord('a.html', 'a', 1, true);
        $second = new PageRecord('b.html', 'b', 1, false);

        self::assertSame([$first, $second], $writer->records([[$first], [$second]]));
        self::assertSame([], $writer->records([]));
    }

    public function testRecordsRejectsAWorkerThatReportedSomethingElse(): void
    {
        $writer = new CachedPageWriter();

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('A documentation worker reported no written pages.');

        $writer->records([['not a record']]);
    }

    public function testRecordsRejectsAWorkerThatReportedNoList(): void
    {
        $writer = new CachedPageWriter();

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('A documentation worker reported no written pages.');

        $writer->records(['not a list']);
    }
}
