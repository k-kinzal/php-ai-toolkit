<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cache;

use PhpAiToolkit\DocGen\Cache\PageRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PageRecord::class)]
final class PageRecordTest extends TestCase
{
    public function testGetExposesWhatWasWritten(): void
    {
        $record = new PageRecord('demo/pkg/index.html', 'signature', 128, true);

        self::assertSame('demo/pkg/index.html', $record->path);
        self::assertSame('signature', $record->signature);
        self::assertSame(128, $record->size);
        self::assertTrue($record->rendered);
    }

    public function testGetExposesAPageThatWasLeftAlone(): void
    {
        $record = new PageRecord('index.html', 'signature', 0, false);

        self::assertFalse($record->rendered);
        self::assertSame(0, $record->size);
    }
}
