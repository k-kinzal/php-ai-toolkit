<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Cli;

use PhpAiToolkit\Doctest\Cli\DoctestOutputWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DoctestOutputWriter::class)]
final class DoctestOutputWriterTest extends TestCase
{
    public function testWriteSendsToTheStandardOutputClosure(): void
    {
        $written = [];
        $writer = new DoctestOutputWriter(static function (string $message) use (&$written): void {
            $written[] = $message;
        });

        $writer->write('out');

        self::assertSame(['out'], $written);
    }

    public function testWriteErrorSendsToTheStandardErrorClosure(): void
    {
        $written = [];
        $writer = new DoctestOutputWriter(null, static function (string $message) use (&$written): void {
            $written[] = $message;
        });

        $writer->writeError('err');

        self::assertSame(['err'], $written);
    }
}
