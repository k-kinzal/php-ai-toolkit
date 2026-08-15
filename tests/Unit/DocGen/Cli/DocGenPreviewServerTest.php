<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PhpAiToolkit\DocGen\Cli\DocGenPreviewServer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DocGenPreviewServer::class)]
final class DocGenPreviewServerTest extends TestCase
{
    public function testCommandBuildsBuiltInServerInvocation(): void
    {
        $command = (new DocGenPreviewServer())->command('/tmp/site', '127.0.0.1:8090');

        self::assertStringContainsString(' -S ', $command);
        self::assertStringContainsString('127.0.0.1:8090', $command);
        self::assertStringContainsString(' -t ', $command);
        self::assertStringContainsString('/tmp/site', $command);
    }

    public function testServeInvokesLauncherWithCommandAndReturnsItsExitCode(): void
    {
        $captured = '';
        $server = new DocGenPreviewServer(static function (string $command) use (&$captured): int {
            $captured = $command;

            return 5;
        });

        self::assertSame(5, $server->serve('/tmp/site', '0.0.0.0:8080'));
        self::assertSame($server->command('/tmp/site', '0.0.0.0:8080'), $captured);
    }

    public function testServeNormalizesNonIntegerLauncherResultToZero(): void
    {
        $server = new DocGenPreviewServer(static function (string $command): string {
            return $command;
        });

        self::assertSame(0, $server->serve('/tmp/site', '127.0.0.1:8090'));
    }
}
