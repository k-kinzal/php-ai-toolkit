<?php

declare(strict_types=1);

namespace Tests\Unit\Installer\Cli\Command;

use function file_put_contents;
use function is_dir;
use function mkdir;

use PhpAiToolkit\Installer\Cli\Command\SkillFilesystemOperator;
use PhpAiToolkit\Installer\Cli\Command\SkillInstallationRunner;
use PhpAiToolkit\Installer\Cli\Command\SkillInstallationWriter;
use PhpAiToolkit\Installer\Cli\Command\SkillInstaller;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function sys_get_temp_dir;
use function uniqid;

#[CoversClass(SkillInstallationRunner::class)]
#[UsesClass(SkillFilesystemOperator::class)]
#[UsesClass(SkillInstallationWriter::class)]
#[UsesClass(SkillInstaller::class)]
final class SkillInstallationRunnerTest extends TestCase
{
    public function testInstallInstallsSkillsIntoEachTargetDirectory(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        mkdir($path . '/package/skills/test-skill', 0755, true);
        mkdir($path . '/project', 0755, true);
        file_put_contents($path . '/package/skills/test-skill/SKILL.md', 'content');
        $output = [];
        $filesystemOperator = new SkillFilesystemOperator();
        $writer = new SkillInstallationWriter(static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        try {
            $stats = (new SkillInstallationRunner(
                $filesystemOperator,
                new SkillInstaller($filesystemOperator, $writer),
                $writer,
            ))->install(
                $path . '/project',
                $path . '/package/skills',
                ['test-skill'],
                ['.claude' => '.claude/skills', '.agents' => '.agents/skills'],
                false,
                true,
            );

            self::assertSame(['installed' => 2, 'skipped' => 0, 'errors' => 0], $stats);
            self::assertTrue(is_dir($path . '/project/.claude/skills/test-skill'));
            self::assertTrue(is_dir($path . '/project/.agents/skills/test-skill'));
        } finally {
            (new SkillFilesystemOperator())->remove($path);
        }
    }
}
