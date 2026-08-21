<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Reporting;

use PhpAiToolkit\ScopeGuard\Analysis\Violation;
use PhpAiToolkit\ScopeGuard\Reporting\ViolationFieldComparator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ViolationFieldComparator::class)]
#[UsesClass(Violation::class)]
final class ViolationFieldComparatorTest extends TestCase
{
    public function testCompareOrdersByPath(): void
    {
        $left = new Violation('src/A.php', 9, 'out_of_scope', 'App\\A', 'A.');
        $right = new Violation('src/B.php', 1, 'out_of_scope', 'App\\B', 'B.');

        self::assertLessThan(0, (new ViolationFieldComparator())->compare($left, $right, 'path'));
    }

    public function testCompareOrdersByLine(): void
    {
        $left = new Violation('src/A.php', 9, 'out_of_scope', 'App\\A', 'A.');
        $right = new Violation('src/A.php', 1, 'out_of_scope', 'App\\A', 'A.');

        self::assertGreaterThan(0, (new ViolationFieldComparator())->compare($left, $right, 'line'));
    }

    public function testCompareOrdersByRule(): void
    {
        $left = new Violation('src/A.php', 1, 'invalid_scope', 'App\\A', 'A.');
        $right = new Violation('src/A.php', 1, 'out_of_scope', 'App\\A', 'A.');

        self::assertLessThan(0, (new ViolationFieldComparator())->compare($left, $right, 'rule'));
    }

    public function testCompareOrdersBySymbol(): void
    {
        $left = new Violation('src/A.php', 1, 'out_of_scope', 'App\\A', 'A.');
        $right = new Violation('src/A.php', 1, 'out_of_scope', 'App\\B', 'B.');

        self::assertLessThan(0, (new ViolationFieldComparator())->compare($left, $right, 'symbol'));
    }
}
