<?php

declare(strict_types=1);

namespace Tests\Unit\TestReporter\Subscriber;

use PhpStanAiRules\TestReporter\Subscriber\TestConsideredRiskySubscriber;
use PhpStanAiRules\TestReporter\TestIssueCollector;
use PHPUnit\Event\Test\ConsideredRisky;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\PhpUnitEventFactory;

#[CoversClass(TestConsideredRiskySubscriber::class)]
final class TestConsideredRiskySubscriberTest extends TestCase
{
    public function testNotifyDelegatesToCollector(): void
    {
        $collector = new TestIssueCollector();
        $subscriber = new TestConsideredRiskySubscriber($collector);

        $subscriber->notify(new ConsideredRisky(
            PhpUnitEventFactory::createTelemetryInfo(),
            PhpUnitEventFactory::createTestMethod('Tests\Unit\FooTest', 'testBar', '/foo.php', 1),
            'No assertions',
        ));

        self::assertTrue($collector->hasIssues());
    }
}
