<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Subscriber;

use function class_implements;
use function interface_exists;

use Override;
use PhpAiToolkit\PhpUnit\TestReporter\Subscriber\TestFailedSubscriber;
use PHPUnit\Event\Test\FailedSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestFailedSubscriber::class)]
final class TestFailedSubscriberTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        if (!interface_exists('PHPUnit\Runner\Extension\Extension')) {
            self::markTestSkipped('Requires PHPUnit 10 event extension API.');
        }
    }

    public function testSubscriberImplementsFailedSubscriber(): void
    {
        $interfaces = class_implements(TestFailedSubscriber::class);

        self::assertIsArray($interfaces);
        self::assertContains(FailedSubscriber::class, $interfaces);
    }
}
