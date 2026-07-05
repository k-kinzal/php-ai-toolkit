<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Subscriber;

use function interface_exists;
use Override;
use PhpAiToolkit\PhpUnit\TestReporter\Subscriber\TestErroredSubscriber;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueCollector;
use PHPUnit\Event\Code\TestDox;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Code\Throwable;
use PHPUnit\Event\Telemetry\CpuTime;
use PHPUnit\Event\Telemetry\Duration;
use PHPUnit\Event\Telemetry\GarbageCollectorStatus;
use PHPUnit\Event\Telemetry\HRTime;
use PHPUnit\Event\Telemetry\Info;
use PHPUnit\Event\Telemetry\MemoryUsage;
use PHPUnit\Event\Telemetry\Snapshot;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\TestData\TestDataCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use PHPUnit\Metadata\MetadataCollection;

#[CoversClass(TestErroredSubscriber::class)]
final class TestErroredSubscriberTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        if (!interface_exists('PHPUnit\Runner\Extension\Extension')) {
            self::markTestSkipped('Requires PHPUnit 10 event extension API.');
        }
    }

    public function testNotifyDelegatesToCollector(): void
    {
        $collector = new TestIssueCollector();
        $subscriber = new TestErroredSubscriber($collector);
        $duration = Duration::fromSecondsAndNanoseconds(0, 0);
        $memory = MemoryUsage::fromBytes(0);
        $garbageCollectorStatus = new GarbageCollectorStatus(0, 0, 0, 0, 0.0, 0.0, 0.0, 0.0, false, false, false, 0);
        $telemetryInfo = PHP_VERSION_ID >= 80500
            ? new Info(
                new Snapshot(
                    HRTime::fromSecondsAndNanoseconds(0, 0),
                    $memory,
                    $memory,
                    $garbageCollectorStatus,
                    CpuTime::fromSecondsAndNanoseconds(0, 0),
                    CpuTime::fromSecondsAndNanoseconds(0, 0),
                    CpuTime::fromSecondsAndNanoseconds(0, 0),
                ),
                $duration,
                $memory,
                $duration,
                $memory,
                CpuTime::fromSecondsAndNanoseconds(0, 0),
                CpuTime::fromSecondsAndNanoseconds(0, 0),
                CpuTime::fromSecondsAndNanoseconds(0, 0),
                CpuTime::fromSecondsAndNanoseconds(0, 0),
                CpuTime::fromSecondsAndNanoseconds(0, 0),
                CpuTime::fromSecondsAndNanoseconds(0, 0),
            )
            : new Info(
                new Snapshot(HRTime::fromSecondsAndNanoseconds(0, 0), $memory, $memory, $garbageCollectorStatus),
                $duration,
                $memory,
                $duration,
                $memory,
            );
        $testMethod = new TestMethod(
            self::class,
            'testBar',
            '/foo.php',
            1,
            new TestDox('', '', ''),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );

        $subscriber->notify(new Errored(
            $telemetryInfo,
            $testMethod,
            new Throwable('TypeError', 'error', 'error', '', null),
        ));

        self::assertTrue($collector->hasIssues());
    }
}
