<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Subscriber;

use function array_merge;
use function class_implements;
use function dirname;
use function fclose;
use function getenv;
use function interface_exists;

use Override;

use const PHP_BINARY;

use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber as ExecutionFinishedSubscriberInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;

use function proc_close;
use function proc_open;
use function stream_get_contents;

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

        $pipes = [];
        $process = proc_open(
            [
                PHP_BINARY,
                'vendor/bin/phpunit',
                '--configuration',
                'tests/Fixture/TestReporter/phpunit-extension.xml.dist',
                '--colors=never',
            ],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            dirname(__DIR__, 5),
            $environment,
        );

        self::assertIsResource($process);

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        self::assertIsString($stdout);
        self::assertIsString($stderr);
        self::assertNotSame(0, $exitCode);
        self::assertStringContainsString('--- PHPUnit: 1 failure, 1 error, 1 risky ---', $stdout . $stderr);
    }
}
