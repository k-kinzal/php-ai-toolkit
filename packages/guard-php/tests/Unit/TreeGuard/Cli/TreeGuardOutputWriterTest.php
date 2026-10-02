<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Cli\TreeGuardOutputWriter;

/**
 * @covers \Toolkit\TreeGuard\Cli\TreeGuardOutputWriter
 */
#[CoversClass(TreeGuardOutputWriter::class)]
final class TreeGuardOutputWriterTest extends TestCase
{
    public function testWriteUsesStdoutClosure(): void
    {
        $output = '';
        $writer = new TreeGuardOutputWriter(static function (string $message) use (&$output): void {
            $output .= $message;
        });

        $writer->write('hello');

        self::assertSame('hello', $output);
    }

    public function testWriteErrorUsesStderrClosure(): void
    {
        $error = '';
        $writer = new TreeGuardOutputWriter(null, static function (string $message) use (&$error): void {
            $error .= $message;
        });

        $writer->writeError('problem');

        self::assertSame('problem', $error);
    }
}
