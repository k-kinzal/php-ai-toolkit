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
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\TestData\TestDataCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Metadata\MetadataCollection;
use Tests\Fixture\PhpUnitInternalObjectFactory;

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
        $telemetryInfo = PhpUnitInternalObjectFactory::telemetryInfo();
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
