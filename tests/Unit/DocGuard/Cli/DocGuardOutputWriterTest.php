<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Cli\DocGuardOutputWriter;

/**
 * @covers \Toolkit\DocGuard\Cli\DocGuardOutputWriter
 */
#[CoversClass(DocGuardOutputWriter::class)]
final class DocGuardOutputWriterTest extends TestCase
{
    public function testWriteUsesStdoutClosure(): void
    {
        $output = '';
        $writer = new DocGuardOutputWriter(static function (string $message) use (&$output): void {
            $output .= $message;
        });

        $writer->write('hello');

        self::assertSame('hello', $output);
    }

    public function testWriteErrorUsesStderrClosure(): void
    {
        $error = '';
        $writer = new DocGuardOutputWriter(null, static function (string $message) use (&$error): void {
            $error .= $message;
        });

        $writer->writeError('problem');

        self::assertSame('problem', $error);
    }
}
