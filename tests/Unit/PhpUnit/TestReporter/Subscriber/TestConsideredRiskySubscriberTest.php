<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Subscriber;

use function class_implements;
use function interface_exists;

use Override;
use PhpAiToolkit\PhpUnit\TestReporter\Subscriber\TestConsideredRiskySubscriber;
use PHPUnit\Event\Test\ConsideredRiskySubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestConsideredRiskySubscriber::class)]
final class TestConsideredRiskySubscriberTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        if (!interface_exists('PHPUnit\Runner\Extension\Extension')) {
            self::markTestSkipped('Requires PHPUnit 10 event extension API.');
        }
    }

    public function testSubscriberImplementsConsideredRiskySubscriber(): void
    {
        $interfaces = class_implements(TestConsideredRiskySubscriber::class);

        self::assertIsArray($interfaces);
        self::assertContains(ConsideredRiskySubscriber::class, $interfaces);
    }
}
