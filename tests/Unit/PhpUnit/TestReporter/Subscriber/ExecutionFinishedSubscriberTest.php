<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Subscriber;

use function class_implements;
use function interface_exists;

use Override;
use PhpAiToolkit\PhpUnit\TestReporter\Subscriber\ExecutionFinishedSubscriber;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber as ExecutionFinishedSubscriberInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExecutionFinishedSubscriber::class)]
final class ExecutionFinishedSubscriberTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        if (!interface_exists('PHPUnit\Runner\Extension\Extension')) {
            self::markTestSkipped('Requires PHPUnit 10 event extension API.');
        }
    }

    public function testSubscriberImplementsExecutionFinishedSubscriber(): void
    {
        $interfaces = class_implements(ExecutionFinishedSubscriber::class);

        self::assertIsArray($interfaces);
        self::assertContains(ExecutionFinishedSubscriberInterface::class, $interfaces);
    }
}
