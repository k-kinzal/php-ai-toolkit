<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PhpAiToolkit\DocGen\Cli\DocGenOutputWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Cli\DocGenOutputWriter
 */
#[CoversClass(DocGenOutputWriter::class)]
final class DocGenOutputWriterTest extends TestCase
{
    public function testWriteSendsMessageToStdoutClosure(): void
    {
        $output = '';
        $errors = '';
        $writer = new DocGenOutputWriter(
            static function (string $message) use (&$output): void {
                $output .= $message;
            },
            static function (string $message) use (&$errors): void {
                $errors .= $message;
            },
        );

        $writer->write('hello ');
        $writer->write('world');

        self::assertSame('hello world', $output);
        self::assertSame('', $errors);
    }

    public function testWriteErrorSendsMessageToStderrClosure(): void
    {
        $output = '';
        $errors = '';
        $writer = new DocGenOutputWriter(
            static function (string $message) use (&$output): void {
                $output .= $message;
            },
            static function (string $message) use (&$errors): void {
                $errors .= $message;
            },
        );

        $writer->writeError('failure');

        self::assertSame('', $output);
        self::assertSame('failure', $errors);
    }
}
