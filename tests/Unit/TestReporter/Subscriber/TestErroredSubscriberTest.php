<?php

declare(strict_types=1);

namespace Tests\Unit\TestReporter\Subscriber;

use PhpStanAiRules\TestReporter\Subscriber\TestErroredSubscriber;
use PhpStanAiRules\TestReporter\TestIssueCollector;
use PHPUnit\Event\Code\TestDox;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Code\Throwable;
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
    public function testNotifyDelegatesToCollector(): void
    {
        $collector = new TestIssueCollector();
        $subscriber = new TestErroredSubscriber($collector);
        $time = HRTime::fromSecondsAndNanoseconds(0, 0);
        $memory = MemoryUsage::fromBytes(0);
        $gc = new GarbageCollectorStatus(0, 0, 0, 0, 0.0, 0.0, 0.0, 0.0, false, false, false, 0);
        $snapshot = new Snapshot($time, $memory, $memory, $gc);
        $duration = Duration::fromSecondsAndNanoseconds(0, 0);
        $telemetryInfo = new Info($snapshot, $duration, $memory, $duration, $memory);
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
