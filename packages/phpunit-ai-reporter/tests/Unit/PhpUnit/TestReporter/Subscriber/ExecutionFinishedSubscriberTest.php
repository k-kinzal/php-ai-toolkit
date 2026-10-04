<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Subscriber;

use function array_merge;
use function class_implements;
use function getenv;
use function interface_exists;

use Override;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber as ExecutionFinishedSubscriberInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;
use Tests\Fixture\TestReporter\PhpUnitFixtureProcess;
use Toolkit\PhpUnit\TestReporter\Subscriber\ExecutionFinishedSubscriber;

/**
 * @coversNothing
 * @large
 */
#[CoversNothing]
#[Large]
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

    public function testNotifyWritesReportWhenExecutionFinishesThroughPhpUnitRunner(): void
    {
        $environment = getenv();
        unset($environment['PARATEST']);
        $environment = array_merge($environment, ['AI_AGENT' => '1']);

        $result = PhpUnitFixtureProcess::runExtension($environment);

        self::assertNotSame(0, $result->exitCode());
        self::assertStringContainsString('--- PHPUnit: 1 failure, 1 error, 1 risky ---', $result->output());
    }
}
