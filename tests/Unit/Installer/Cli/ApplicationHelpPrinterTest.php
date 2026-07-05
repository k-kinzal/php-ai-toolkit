<?php

declare(strict_types=1);

namespace Tests\Unit\Installer\Cli;

use function implode;

use PhpAiToolkit\Installer\Cli\ApplicationHelpPrinter;
use PhpAiToolkit\Installer\Cli\CliOutputWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApplicationHelpPrinter::class)]
#[UsesClass(CliOutputWriter::class)]
final class ApplicationHelpPrinterTest extends TestCase
{
    public function testPrintWritesHelpText(): void
    {
        $output = [];
        $printer = new ApplicationHelpPrinter(
            new CliOutputWriter(static function (string $message) use (&$output): void {
                $output[] = $message;
            }),
            '1.2.3',
        );

        $printer->print();

        $content = implode("\n", $output);
        self::assertStringContainsString('php-ai-toolkit v1.2.3', $content);
        self::assertStringContainsString('Usage:', $content);
    }
}
