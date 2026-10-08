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

        $configurationFile = dirname(__DIR__, 5) . '/tests/TestReporter/phpunit-extension.xml.dist';
        $phpunitPackage = \Composer\InstalledVersions::getInstallPath('phpunit/phpunit');
        self::assertIsString($phpunitPackage);
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, $phpunitPackage . '/phpunit', '--configuration', $configurationFile, '--colors=never'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            dirname($configurationFile),
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
        $output = $stdout . $stderr;

        self::assertNotSame(0, $exitCode);
        self::assertStringContainsString('[FAILED]', $output);
        self::assertStringContainsString('Tests\TestReporter\FailingTest::testFails', $output);
    }
}
