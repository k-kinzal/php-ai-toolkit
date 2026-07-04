<?php

declare(strict_types=1);

namespace Tests\Unit\TestReporter;

use PhpStanAiRules\TestReporter\TestIssue;
use PhpStanAiRules\TestReporter\TestIssueCollector;
use PHPUnit\Event\Code\ComparisonFailure;
use PHPUnit\Event\Code\TestDox;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Code\Throwable;
use PHPUnit\Event\Telemetry\Duration;
use PHPUnit\Event\Telemetry\GarbageCollectorStatus;
use PHPUnit\Event\Telemetry\HRTime;
use PHPUnit\Event\Telemetry\Info;
use PHPUnit\Event\Telemetry\MemoryUsage;
use PHPUnit\Event\Telemetry\Snapshot;
use PHPUnit\Event\Test\ConsideredRisky;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\TestData\TestDataCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Metadata\MetadataCollection;

#[CoversClass(TestIssueCollector::class)]
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
        $time = HRTime::fromSecondsAndNanoseconds(0, 0);
        $memory = MemoryUsage::fromBytes(0);
        $gc = new GarbageCollectorStatus(0, 0, 0, 0, 0.0, 0.0, 0.0, 0.0, false, false, false, 0);
        $snapshot = new Snapshot($time, $memory, $memory, $gc);
        $duration = Duration::fromSecondsAndNanoseconds(0, 0);
        $telemetryInfo = new Info($snapshot, $duration, $memory, $duration, $memory);
        $testMethod = new TestMethod(
            self::class,
            'testBar',
            '/path/to/tests/Unit/FooTest.php',
            42,
            new TestDox('', '', ''),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );

        $event = new Failed(
            $telemetryInfo,
            $testMethod,
            new Throwable(
                'PHPUnit\Framework\ExpectationFailedException',
                'Failed asserting that false is true.',
                'Failed asserting that false is true.',
                "/path/to/tests/Unit/FooTest.php:42\n/path/to/vendor/phpunit/phpunit/src/Framework/Assert.php:100",
                null,
            ),
            new ComparisonFailure('true', 'false', "--- Expected\n+++ Actual\n-true\n+false"),
        );

        $collector->recordFailure($event);

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
        $time = HRTime::fromSecondsAndNanoseconds(0, 0);
        $memory = MemoryUsage::fromBytes(0);
        $gc = new GarbageCollectorStatus(0, 0, 0, 0, 0.0, 0.0, 0.0, 0.0, false, false, false, 0);
        $snapshot = new Snapshot($time, $memory, $memory, $gc);
        $duration = Duration::fromSecondsAndNanoseconds(0, 0);
        $telemetryInfo = new Info($snapshot, $duration, $memory, $duration, $memory);
        $testMethod = new TestMethod(
            self::class,
            'testBar',
            '/path/to/tests/Unit/FooTest.php',
            42,
            new TestDox('', '', ''),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );

        $event = new Failed(
            $telemetryInfo,
            $testMethod,
            new Throwable(
                'PHPUnit\Framework\ExpectationFailedException',
                'Some assertion failed.',
                'Some assertion failed.',
                '/path/to/tests/Unit/FooTest.php:42',
                null,
            ),
            null,
        );

        $collector->recordFailure($event);

        $issues = $collector->getIssues();
        self::assertNull($issues[0]->diff);
    }

    public function testRecordErrorCreatesErrorIssue(): void
    {
        $collector = new TestIssueCollector();
        $time = HRTime::fromSecondsAndNanoseconds(0, 0);
        $memory = MemoryUsage::fromBytes(0);
        $gc = new GarbageCollectorStatus(0, 0, 0, 0, 0.0, 0.0, 0.0, 0.0, false, false, false, 0);
        $snapshot = new Snapshot($time, $memory, $memory, $gc);
        $duration = Duration::fromSecondsAndNanoseconds(0, 0);
        $telemetryInfo = new Info($snapshot, $duration, $memory, $duration, $memory);
        $testMethod = new TestMethod(
            self::class,
            'testBaz',
            '/path/to/tests/Unit/BarTest.php',
            18,
            new TestDox('', '', ''),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );

        $event = new Errored(
            $telemetryInfo,
            $testMethod,
            new Throwable(
                'TypeError',
                'Argument 1 must be of type int, string given',
                'TypeError: Argument 1 must be of type int, string given',
                "/path/to/tests/Unit/BarTest.php:18\n/path/to/src/Bar.php:45\n/path/to/vendor/phpunit/phpunit/src/Framework/TestCase.php:200",
                null,
            ),
        );

        $collector->recordError($event);

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
        $time = HRTime::fromSecondsAndNanoseconds(0, 0);
        $memory = MemoryUsage::fromBytes(0);
        $gc = new GarbageCollectorStatus(0, 0, 0, 0, 0.0, 0.0, 0.0, 0.0, false, false, false, 0);
        $snapshot = new Snapshot($time, $memory, $memory, $gc);
        $duration = Duration::fromSecondsAndNanoseconds(0, 0);
        $telemetryInfo = new Info($snapshot, $duration, $memory, $duration, $memory);
        $testMethod = new TestMethod(
            self::class,
            'testExpiry',
            '/path/to/tests/Unit/CacheTest.php',
            55,
            new TestDox('', '', ''),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );

        $event = new ConsideredRisky(
            $telemetryInfo,
            $testMethod,
            'This test did not perform any assertions',
        );

        $collector->recordRisky($event);

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
        $time = HRTime::fromSecondsAndNanoseconds(0, 0);
        $memory = MemoryUsage::fromBytes(0);
        $gc = new GarbageCollectorStatus(0, 0, 0, 0, 0.0, 0.0, 0.0, 0.0, false, false, false, 0);
        $snapshot = new Snapshot($time, $memory, $memory, $gc);
        $duration = Duration::fromSecondsAndNanoseconds(0, 0);
        $telemetryInfo = new Info($snapshot, $duration, $memory, $duration, $memory);
        $testMethod = new TestMethod(
            self::class,
            'testBaz',
            '/path/to/tests/Unit/BarTest.php',
            18,
            new TestDox('', '', ''),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );

        $event = new Errored(
            $telemetryInfo,
            $testMethod,
            new Throwable(
                'TypeError',
                'Some error',
                'Some error',
                "/path/to/tests/Unit/BarTest.php:18\n/path/to/src/Service/UserService.php:45\n/path/to/vendor/phpunit/phpunit/src/Framework/TestCase.php:200",
                null,
            ),
        );

        $collector->recordError($event);

        $issues = $collector->getIssues();
        self::assertSame('/path/to/src/Service/UserService.php', $issues[0]->sourceFile);
        self::assertSame(45, $issues[0]->sourceLine);
    }

    public function testExtractSourceLocationSkipsVendorFrames(): void
    {
        $collector = new TestIssueCollector();
        $time = HRTime::fromSecondsAndNanoseconds(0, 0);
        $memory = MemoryUsage::fromBytes(0);
        $gc = new GarbageCollectorStatus(0, 0, 0, 0, 0.0, 0.0, 0.0, 0.0, false, false, false, 0);
        $snapshot = new Snapshot($time, $memory, $memory, $gc);
        $duration = Duration::fromSecondsAndNanoseconds(0, 0);
        $telemetryInfo = new Info($snapshot, $duration, $memory, $duration, $memory);
        $testMethod = new TestMethod(
            self::class,
            'testBar',
            '/path/to/tests/Unit/FooTest.php',
            10,
            new TestDox('', '', ''),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );

        $event = new Errored(
            $telemetryInfo,
            $testMethod,
            new Throwable(
                'Error',
                'Some error',
                'Some error',
                "/path/to/tests/Unit/FooTest.php:10\n/path/to/vendor/some/package/File.php:20",
                null,
            ),
        );

        $collector->recordError($event);

        $issues = $collector->getIssues();
        self::assertNull($issues[0]->sourceFile);
    }

    public function testMultipleIssuesCollectedInOrder(): void
    {
        $collector = new TestIssueCollector();
        $time = HRTime::fromSecondsAndNanoseconds(0, 0);
        $memory = MemoryUsage::fromBytes(0);
        $gc = new GarbageCollectorStatus(0, 0, 0, 0, 0.0, 0.0, 0.0, 0.0, false, false, false, 0);
        $snapshot = new Snapshot($time, $memory, $memory, $gc);
        $duration = Duration::fromSecondsAndNanoseconds(0, 0);
        $telemetryInfo = new Info($snapshot, $duration, $memory, $duration, $memory);
        $failedTestMethod = new TestMethod(
            self::class,
            'testA',
            '/a.php',
            1,
            new TestDox('', '', ''),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );
        $erroredTestMethod = new TestMethod(
            self::class,
            'testB',
            '/b.php',
            2,
            new TestDox('', '', ''),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );
        $riskyTestMethod = new TestMethod(
            self::class,
            'testC',
            '/c.php',
            3,
            new TestDox('', '', ''),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );

        $collector->recordFailure(new Failed(
            $telemetryInfo,
            $failedTestMethod,
            new Throwable('Exception', 'fail A', 'fail A', '', null),
            null,
        ));
        $collector->recordError(new Errored(
            $telemetryInfo,
            $erroredTestMethod,
            new Throwable('Exception', 'error B', 'error B', '', null),
        ));
        $collector->recordRisky(new ConsideredRisky(
            $telemetryInfo,
            $riskyTestMethod,
            'risky C',
        ));

        $issues = $collector->getIssues();
        self::assertCount(3, $issues);
        self::assertSame(TestIssue::TYPE_FAILED, $issues[0]->type);
        self::assertSame(TestIssue::TYPE_ERROR, $issues[1]->type);
        self::assertSame(TestIssue::TYPE_RISKY, $issues[2]->type);
    }
}
