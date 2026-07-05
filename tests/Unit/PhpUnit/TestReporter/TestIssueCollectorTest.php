<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use PhpAiToolkit\PhpUnit\TestReporter\TestIssue;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueCollector;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueInput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestIssueCollector::class)]
#[CoversClass(TestIssueInput::class)]
final class TestIssueCollectorTest extends TestCase
{
    public function testHasIssuesReturnsFalseWhenEmpty(): void
    {
        $collector = new TestIssueCollector();

        self::assertFalse($collector->hasIssues());
    }

    public function testGetIssuesReturnsEmptyArrayWhenEmpty(): void
    {
        $collector = new TestIssueCollector();

        self::assertSame([], $collector->getIssues());
    }

    public function testRecordFailureCreatesFailedIssueWithDiff(): void
    {
        $collector = new TestIssueCollector();

        $collector->record(new TestIssueInput(
            TestIssue::TYPE_FAILED,
            self::class . '::testBar',
            self::class . '::testBar',
            '/path/to/tests/Unit/FooTest.php',
            42,
            'Failed asserting that false is true.',
            "--- Expected\n+++ Actual\n-true\n+false",
            "/path/to/tests/Unit/FooTest.php:42\n/path/to/vendor/phpunit/phpunit/src/Framework/Assert.php:100",
        ));

        self::assertTrue($collector->hasIssues());

        $issues = $collector->getIssues();
        self::assertCount(1, $issues);
        self::assertSame(TestIssue::TYPE_FAILED, $issues[0]->type);
        self::assertSame(self::class . '::testBar', $issues[0]->testId);
        self::assertSame(self::class . '::testBar', $issues[0]->testName);
        self::assertSame('/path/to/tests/Unit/FooTest.php', $issues[0]->testFile);
        self::assertSame(42, $issues[0]->testLine);
        self::assertSame('Failed asserting that false is true.', $issues[0]->message);
        self::assertSame("--- Expected\n+++ Actual\n-true\n+false", $issues[0]->diff);
    }

    public function testRecordFailureWithoutComparisonFailureHasNullDiff(): void
    {
        $collector = new TestIssueCollector();

        $collector->record(new TestIssueInput(
            TestIssue::TYPE_FAILED,
            self::class . '::testBar',
            self::class . '::testBar',
            '/path/to/tests/Unit/FooTest.php',
            42,
            'Some assertion failed.',
            null,
            '/path/to/tests/Unit/FooTest.php:42',
        ));

        $issues = $collector->getIssues();
        self::assertNull($issues[0]->diff);
    }

    public function testRecordErrorCreatesErrorIssue(): void
    {
        $collector = new TestIssueCollector();

        $collector->record(new TestIssueInput(
            TestIssue::TYPE_ERROR,
            self::class . '::testBaz',
            self::class . '::testBaz',
            '/path/to/tests/Unit/BarTest.php',
            18,
            'Argument 1 must be of type int, string given',
            null,
            "/path/to/tests/Unit/BarTest.php:18\n/path/to/src/Bar.php:45\n/path/to/vendor/phpunit/phpunit/src/Framework/TestCase.php:200",
        ));

        $issues = $collector->getIssues();
        self::assertCount(1, $issues);
        self::assertSame(TestIssue::TYPE_ERROR, $issues[0]->type);
        self::assertSame(self::class . '::testBaz', $issues[0]->testId);
        self::assertSame('Argument 1 must be of type int, string given', $issues[0]->message);
        self::assertNull($issues[0]->diff);
    }

    public function testRecordRiskyCreatesRiskyIssue(): void
    {
        $collector = new TestIssueCollector();

        $collector->record(new TestIssueInput(
            TestIssue::TYPE_RISKY,
            self::class . '::testExpiry',
            self::class . '::testExpiry',
            '/path/to/tests/Unit/CacheTest.php',
            55,
            'This test did not perform any assertions',
        ));

        $issues = $collector->getIssues();
        self::assertCount(1, $issues);
        self::assertSame(TestIssue::TYPE_RISKY, $issues[0]->type);
        self::assertSame('This test did not perform any assertions', $issues[0]->message);
        self::assertNull($issues[0]->sourceFile);
        self::assertNull($issues[0]->sourceLine);
    }

    public function testRecordErrorExtractsSourceLocationFromStackTrace(): void
    {
        $collector = new TestIssueCollector();

        $collector->record(new TestIssueInput(
            TestIssue::TYPE_ERROR,
            self::class . '::testBaz',
            self::class . '::testBaz',
            '/path/to/tests/Unit/BarTest.php',
            18,
            'Some error',
            null,
            "/path/to/tests/Unit/BarTest.php:18\n/path/to/src/Service/UserService.php:45\n/path/to/vendor/phpunit/phpunit/src/Framework/TestCase.php:200",
        ));

        $issues = $collector->getIssues();
        self::assertSame('/path/to/src/Service/UserService.php', $issues[0]->sourceFile);
        self::assertSame(45, $issues[0]->sourceLine);
    }

    public function testExtractSourceLocationSkipsVendorFrames(): void
    {
        $collector = new TestIssueCollector();

        $collector->record(new TestIssueInput(
            TestIssue::TYPE_ERROR,
            self::class . '::testBar',
            self::class . '::testBar',
            '/path/to/tests/Unit/FooTest.php',
            10,
            'Some error',
            null,
            "/path/to/tests/Unit/FooTest.php:10\n/path/to/vendor/some/package/File.php:20",
        ));

        $issues = $collector->getIssues();
        self::assertNull($issues[0]->sourceFile);
    }

    public function testMultipleIssuesCollectedInOrder(): void
    {
        $collector = new TestIssueCollector();

        $collector->record(new TestIssueInput(TestIssue::TYPE_FAILED, 'T::a', 'T::a', '/a.php', 1, 'fail A'));
        $collector->record(new TestIssueInput(TestIssue::TYPE_ERROR, 'T::b', 'T::b', '/b.php', 2, 'error B'));
        $collector->record(new TestIssueInput(TestIssue::TYPE_RISKY, 'T::c', 'T::c', '/c.php', 3, 'risky C'));

        $issues = $collector->getIssues();
        self::assertCount(3, $issues);
        self::assertSame(TestIssue::TYPE_FAILED, $issues[0]->type);
        self::assertSame(TestIssue::TYPE_ERROR, $issues[1]->type);
        self::assertSame(TestIssue::TYPE_RISKY, $issues[2]->type);
    }
}
