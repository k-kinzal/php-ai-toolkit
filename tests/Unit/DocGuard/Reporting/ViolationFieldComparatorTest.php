<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Analysis\Violation;
use Toolkit\DocGuard\Reporting\ViolationFieldComparator;

/**
 * @covers \Toolkit\DocGuard\Reporting\ViolationFieldComparator
 * @uses \Toolkit\DocGuard\Analysis\Violation
 */
#[CoversClass(ViolationFieldComparator::class)]
#[UsesClass(Violation::class)]
final class ViolationFieldComparatorTest extends TestCase
{
    public function testCompareOrdersByPathRuleAndLine(): void
    {
        $readme = new Violation('README.md', 10, 'unexpected_heading', null, '## A', 'A.');
        $docs = new Violation('docs/guide.md', 2, 'missing_heading', '## B', null, 'B.');
        $missing = new Violation('docs/other.md', null, 'missing_document', null, null, 'C.');

        self::assertLessThan(0, (new ViolationFieldComparator())->compare($readme, $docs, 'path'));
        self::assertGreaterThan(0, (new ViolationFieldComparator())->compare($readme, $docs, 'rule'));
        self::assertGreaterThan(0, (new ViolationFieldComparator())->compare($readme, $docs, 'line'));
        self::assertLessThan(0, (new ViolationFieldComparator())->compare($missing, $docs, 'line'));
    }
}
