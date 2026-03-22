<?php

declare(strict_types=1);

namespace Tests\Unit\TestReporter\Subscriber;

use PhpStanAiRules\TestReporter\Subscriber\TestFailedSubscriber;
use PhpStanAiRules\TestReporter\TestIssueCollector;
use PHPUnit\Event\Code\Throwable;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\PhpUnitEventFactory;

#[CoversClass(TestFailedSubscriber::class)]
final class TestFailedSubscriberTest extends TestCase
{
    public function testNotifyDelegatesToCollector(): void
    {
        $collector = new TestIssueCollector();
        $subscriber = new TestFailedSubscriber($collector);

        $subscriber->notify(new Failed(
            PhpUnitEventFactory::createTelemetryInfo(),
            PhpUnitEventFactory::createTestMethod('Tests\Unit\FooTest', 'testBar', '/foo.php', 1),
            new Throwable('Exception', 'fail', 'fail', '', null),
            null,
        ));

        self::assertTrue($collector->hasIssues());
    }
}
