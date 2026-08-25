<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Analysis\Violation;
use Toolkit\ScopeGuard\Config\ReportConfig;
use Toolkit\ScopeGuard\Reporting\ViolationFieldComparator;
use Toolkit\ScopeGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\ScopeGuard\Reporting\ViolationSorter
 * @uses \Toolkit\ScopeGuard\Config\ReportConfig
 * @uses \Toolkit\ScopeGuard\Analysis\Violation
 * @uses \Toolkit\ScopeGuard\Reporting\ViolationFieldComparator
 */
#[CoversClass(ViolationSorter::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFieldComparator::class)]
final class ViolationSorterTest extends TestCase
{
    public function testSortOrdersByTheConfiguredFields(): void
    {
        $first = new Violation('src/A.php', 9, 'out_of_scope', 'App\\A', 'A.');
        $second = new Violation('src/A.php', 1, 'out_of_scope', 'App\\A', 'A.');
        $sorted = (new ViolationSorter())->sort([$first, $second], new ReportConfig('ai', ['path', 'line']));

        self::assertSame(1, $sorted[0]->line);
    }

    public function testSortKeepsTheGivenOrderWithoutFields(): void
    {
        $first = new Violation('src/B.php', 9, 'out_of_scope', 'App\\B', 'B.');
        $second = new Violation('src/A.php', 1, 'out_of_scope', 'App\\A', 'A.');
        $sorted = (new ViolationSorter())->sort([$first, $second], new ReportConfig('ai', []));

        self::assertSame('src/B.php', $sorted[0]->path);
    }
}
