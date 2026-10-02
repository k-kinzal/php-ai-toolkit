<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Analysis\Violation;
use Toolkit\DocGuard\Config\ReportConfig;
use Toolkit\DocGuard\Reporting\ViolationFieldComparator;
use Toolkit\DocGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\DocGuard\Reporting\ViolationSorter
 * @uses \Toolkit\DocGuard\Config\ReportConfig
 * @uses \Toolkit\DocGuard\Analysis\Violation
 * @uses \Toolkit\DocGuard\Reporting\ViolationFieldComparator
 */
#[CoversClass(ViolationSorter::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFieldComparator::class)]
final class ViolationSorterTest extends TestCase
{
    public function testSortAppliesConfiguredFieldsInOrder(): void
    {
        $late = new Violation('README.md', 30, 'unexpected_heading', null, '## B', 'B.');
        $early = new Violation('README.md', 3, 'unexpected_heading', null, '## A', 'A.');
        $docs = new Violation('docs/guide.md', 1, 'renamed_heading', '## C', '## D', 'C.');

        self::assertSame([$early, $late, $docs], (new ViolationSorter())->sort([$docs, $late, $early], new ReportConfig('ai', ['path', 'line', 'rule'])));
        self::assertSame([$docs, $late, $early], (new ViolationSorter())->sort([$docs, $late, $early], new ReportConfig('ai', ['rule'])));
    }
}
