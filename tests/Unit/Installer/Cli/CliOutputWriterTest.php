<?php

declare(strict_types=1);

namespace Tests\Unit\Installer\Cli;

use PhpAiToolkit\Installer\Cli\CliOutputWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CliOutputWriter::class)]
final class CliOutputWriterTest extends TestCase
{
    public function testWriteSendsMessageToOutput(): void
    {
        $output = [];
        $writer = new CliOutputWriter(static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $writer->write('message');

        self::assertSame(['message'], $output);
    }
}
