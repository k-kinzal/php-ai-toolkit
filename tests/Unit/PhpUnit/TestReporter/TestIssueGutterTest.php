<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use PhpAiToolkit\PhpUnit\TestReporter\TestIssue;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueGutter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestIssueGutter::class)]
final class TestIssueGutterTest extends TestCase
{
    public function testWidthReturnsAtLeastThreeCharacters(): void
    {
        $gutter = new TestIssueGutter();

        self::assertSame(3, $gutter->width([
            new TestIssue(TestIssue::TYPE_FAILED, 'T::a', 'T::a', '/tmp/A.php', 7, 'A'),
        ]));
    }

    public function testLinePadsLineNumberToRequestedWidth(): void
    {
        $gutter = new TestIssueGutter();

        self::assertSame('  7', $gutter->line('7', 3));
    }
}
