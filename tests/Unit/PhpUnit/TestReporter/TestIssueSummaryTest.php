<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use PhpAiToolkit\PhpUnit\TestReporter\TestIssue;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueSummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestIssueSummary::class)]
final class TestIssueSummaryTest extends TestCase
{
    public function testCountByTypeCountsIssuesByType(): void
    {
        $summary = new TestIssueSummary();

        self::assertSame([
            TestIssue::TYPE_FAILED => 2,
            TestIssue::TYPE_ERROR => 1,
        ], $summary->countByType([
            new TestIssue(TestIssue::TYPE_FAILED, 'T::a', 'T::a', '/tmp/A.php', 1, 'A'),
            new TestIssue(TestIssue::TYPE_ERROR, 'T::b', 'T::b', '/tmp/B.php', 2, 'B'),
            new TestIssue(TestIssue::TYPE_FAILED, 'T::c', 'T::c', '/tmp/C.php', 3, 'C'),
        ]));
    }

    public function testBuildCountSummaryFormatsKnownIssueCounts(): void
    {
        $summary = new TestIssueSummary();

        self::assertSame('2 failures, 1 error', $summary->buildCountSummary([
            TestIssue::TYPE_FAILED => 2,
            TestIssue::TYPE_ERROR => 1,
        ], 3));
    }
}
