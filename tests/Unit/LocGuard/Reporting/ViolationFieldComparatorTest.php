<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Reporting;

use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Reporting\ViolationFieldComparator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ViolationFieldComparator::class)]
#[UsesClass(Violation::class)]
final class ViolationFieldComparatorTest extends TestCase
{
    public function testCompareOrdersByConfiguredField(): void
    {
        $left = new Violation('src/A.php', 2, 'file_lines', 10, 5, 'A');
        $right = new Violation('src/B.php', 1, 'method_lines', 20, 10, 'B');

        self::assertLessThan(0, (new ViolationFieldComparator())->compare($left, $right, 'path'));
        self::assertLessThan(0, (new ViolationFieldComparator())->compare($left, $right, 'actual'));
    }
}
