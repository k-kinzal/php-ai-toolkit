<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Cli;

use PhpAiToolkit\ScopeGuard\Cli\ScopeGuardOutputWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ScopeGuardOutputWriter::class)]
final class ScopeGuardOutputWriterTest extends TestCase
{
    public function testWriteSendsToTheStandardOutputClosure(): void
    {
        $written = '';
        $writer = new ScopeGuardOutputWriter(static function (string $message) use (&$written): void {
            $written .= $message;
        });
        $writer->write('report');

        self::assertSame('report', $written);
    }

    public function testWriteErrorSendsToTheStandardErrorClosure(): void
    {
        $written = '';
        $writer = new ScopeGuardOutputWriter(null, static function (string $message) use (&$written): void {
            $written .= $message;
        });
        $writer->writeError('failure');

        self::assertSame('failure', $written);
    }
}
