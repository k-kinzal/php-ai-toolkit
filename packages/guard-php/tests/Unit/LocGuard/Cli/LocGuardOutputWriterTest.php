<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Cli\LocGuardOutputWriter;

/**
 * @covers \Toolkit\LocGuard\Cli\LocGuardOutputWriter
 */
#[CoversClass(LocGuardOutputWriter::class)]
final class LocGuardOutputWriterTest extends TestCase
{
    public function testWriteSendsMessageToStdout(): void
    {
        $stdout = '';
        $writer = new LocGuardOutputWriter(stdout: static function (string $message) use (&$stdout): void {
            $stdout .= $message;
        });

        $writer->write('message');

        self::assertSame('message', $stdout);
    }

    public function testWriteErrorSendsMessageToStderr(): void
    {
        $stderr = '';
        $writer = new LocGuardOutputWriter(stderr: static function (string $message) use (&$stderr): void {
            $stderr .= $message;
        });

        $writer->writeError('error');

        self::assertSame('error', $stderr);
    }
}
