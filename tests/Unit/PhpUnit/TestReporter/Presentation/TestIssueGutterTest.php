<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Presentation;

use PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueGutter;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueGutter
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\TestIssue
 */
#[CoversClass(TestIssueGutter::class)]
#[UsesClass(TestIssue::class)]
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
