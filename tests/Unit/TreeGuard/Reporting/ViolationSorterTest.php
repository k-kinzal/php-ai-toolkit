<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Reporting;

use PhpAiToolkit\TreeGuard\Analysis\Violation;
use PhpAiToolkit\TreeGuard\Config\ReportConfig;
use PhpAiToolkit\TreeGuard\Reporting\ViolationFieldComparator;
use PhpAiToolkit\TreeGuard\Reporting\ViolationSorter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ViolationSorter::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFieldComparator::class)]
final class ViolationSorterTest extends TestCase
{
    public function testSortOrdersByConfiguredFields(): void
    {
        $first = new Violation('src/B', 'max_files', 'src/*', 2, 1, 'B files');
        $second = new Violation('src/A', 'max_files', 'src/*', 2, 1, 'A files');
        $third = new Violation('src/A', 'empty_directory', 'src/**', null, null, 'A empty');

        $sorted = (new ViolationSorter())->sort([$first, $second, $third], new ReportConfig('ai', ['path', 'rule']));

        self::assertSame(['A empty', 'A files', 'B files'], [$sorted[0]->message, $sorted[1]->message, $sorted[2]->message]);
    }

    public function testSortKeepsOrderWhenFieldsAreEqual(): void
    {
        $first = new Violation('src/A', 'max_files', 'src/*', 2, 1, 'first');
        $second = new Violation('src/A', 'max_files', 'src/*', 2, 1, 'second');

        $sorted = (new ViolationSorter())->sort([$first, $second], new ReportConfig('ai', ['path', 'rule']));

        self::assertSame(['first', 'second'], [$sorted[0]->message, $sorted[1]->message]);
    }
}
