<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Subscriber;

use function class_implements;
use function interface_exists;

use Override;
use PhpAiToolkit\PhpUnit\TestReporter\Subscriber\TestErroredSubscriber;
use PHPUnit\Event\Test\ErroredSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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

    public function testSubscriberImplementsErroredSubscriber(): void
    {
        $interfaces = class_implements(TestErroredSubscriber::class);

        self::assertIsArray($interfaces);
        self::assertContains(ErroredSubscriber::class, $interfaces);
    }
}
