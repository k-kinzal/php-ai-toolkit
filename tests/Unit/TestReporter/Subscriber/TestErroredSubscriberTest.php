<?php

declare(strict_types=1);

namespace Tests\Unit\TestReporter\Subscriber;

use PhpStanAiRules\TestReporter\Subscriber\TestErroredSubscriber;
use PhpStanAiRules\TestReporter\TestIssueCollector;
use PHPUnit\Event\Code\Throwable;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\PhpUnitEventFactory;

#[CoversClass(TestErroredSubscriber::class)]
final class TestErroredSubscriberTest extends TestCase
{
    public function testNotifyDelegatesToCollector(): void
    {
        $collector = new TestIssueCollector();
        $subscriber = new TestErroredSubscriber($collector);

        $subscriber->notify(new Errored(
            PhpUnitEventFactory::createTelemetryInfo(),
            PhpUnitEventFactory::createTestMethod('Tests\Unit\FooTest', 'testBar', '/foo.php', 1),
            new Throwable('TypeError', 'error', 'error', '', null),
        ));

        self::assertTrue($collector->hasIssues());
    }
}
