<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Subscriber;

use function array_merge;
use function class_implements;
use function getenv;
use function interface_exists;

use Override;
use PHPUnit\Event\Test\FailedSubscriber;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;
use Tests\Fixture\TestReporter\PhpUnitFixtureProcess;
use Toolkit\PhpUnit\TestReporter\Subscriber\TestFailedSubscriber;

/**
 * @coversNothing
 * @large
 */
#[CoversNothing]
#[Large]
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

    public function testNotifyRecordsFailedTestThroughPhpUnitRunner(): void
    {
        $environment = getenv();
        unset($environment['PARATEST']);
        $environment = array_merge($environment, ['AI_AGENT' => '1']);

        $result = PhpUnitFixtureProcess::runExtension($environment);

        self::assertNotSame(0, $result->exitCode());
        self::assertStringContainsString('[FAILED]', $result->output());
        self::assertStringContainsString('Tests\Fixture\TestReporter\FailingTest::testFails', $result->output());
    }
}
