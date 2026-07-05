<?php

declare(strict_types=1);

namespace Tests\Unit\Installer\Cli\Command;

use PhpAiToolkit\Installer\Cli\Command\SkillInstallationWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SkillInstallationWriter::class)]
final class SkillInstallationWriterTest extends TestCase
{
    public function testWriteSendsMessageToOutput(): void
    {
        $output = [];
        $writer = new SkillInstallationWriter(static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $writer->write('message');

        self::assertSame(['message'], $output);
    }

    public function testSummaryWritesInstallStats(): void
    {
        $output = [];
        $writer = new SkillInstallationWriter(static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $writer->summary(['installed' => 2, 'skipped' => 1, 'errors' => 0]);

        self::assertSame(['Done. 2 skill(s) installed, 1 skipped, 0 error(s).'], $output);
    }
}
