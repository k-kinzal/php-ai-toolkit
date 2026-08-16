<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Diff;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffLine;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DiffLine::class)]
#[UsesClass(DiffStatus::class)]
final class DiffLineTest extends TestCase
{
    public function testStoresTheStateTheTextAndBothLineNumbers(): void
    {
        $line = new DiffLine(DiffStatus::SAME, '<span>code</span>', 12, 14);

        self::assertSame(DiffStatus::SAME, $line->status);
        self::assertSame('<span>code</span>', $line->text);
        self::assertSame(12, $line->baseNumber);
        self::assertSame(14, $line->headNumber);
    }

    public function testALineOnlyOneRevisionHasKeepsOneNumber(): void
    {
        $added = new DiffLine(DiffStatus::ADDED, 'new', null, 3);
        $removed = new DiffLine(DiffStatus::REMOVED, 'gone', 7, null);

        self::assertNull($added->baseNumber);
        self::assertSame(3, $added->headNumber);
        self::assertSame(7, $removed->baseNumber);
        self::assertNull($removed->headNumber);
    }
}
