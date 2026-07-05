<?php

declare(strict_types=1);

namespace Tests\Unit\Installer\Cli;

use function file_put_contents;
use function implode;
use function mkdir;

use PhpAiToolkit\Installer\Cli\ApplicationInstallRunner;
use PhpAiToolkit\Installer\Cli\CliOutputWriter;
use PhpAiToolkit\Installer\Cli\Command\AgentSkillDirectoryDetector;
use PhpAiToolkit\Installer\Cli\Command\InstallCommand;
use PhpAiToolkit\Installer\Cli\Command\PackageSkillDirectoryScanner;
use PhpAiToolkit\Installer\Cli\Command\SkillFilesystemOperator;
use PhpAiToolkit\Installer\Cli\Command\SkillInstallationRunner;
use PhpAiToolkit\Installer\Cli\Command\SkillInstallationWriter;
use PhpAiToolkit\Installer\Cli\Command\SkillInstaller;
use PhpAiToolkit\Installer\RelativePathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function sys_get_temp_dir;
use function uniqid;

#[CoversClass(ApplicationInstallRunner::class)]
#[UsesClass(AgentSkillDirectoryDetector::class)]
#[UsesClass(CliOutputWriter::class)]
#[UsesClass(InstallCommand::class)]
#[UsesClass(PackageSkillDirectoryScanner::class)]
#[UsesClass(RelativePathResolver::class)]
#[UsesClass(SkillFilesystemOperator::class)]
#[UsesClass(SkillInstallationRunner::class)]
#[UsesClass(SkillInstallationWriter::class)]
#[UsesClass(SkillInstaller::class)]
final class ApplicationInstallRunnerTest extends TestCase
{
    public function testRunWritesHeaderAndRunsInstallCommand(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        $projectRoot = $path . '/project';
        $packageRoot = $path . '/package';
        mkdir($packageRoot . '/skills/test-skill', 0755, true);
        mkdir($projectRoot, 0755, true);
        file_put_contents($packageRoot . '/skills/test-skill/SKILL.md', 'test');
        $output = [];

        try {
            $exitCode = (new ApplicationInstallRunner(
                $projectRoot,
                $packageRoot,
                new CliOutputWriter(static function (string $message) use (&$output): void {
                    $output[] = $message;
                }),
                '1.2.3',
            ))->run(false, true);

            self::assertSame(0, $exitCode);
            self::assertStringContainsString('php-ai-toolkit v1.2.3', implode("\n", $output));
            self::assertStringContainsString('(copied)', implode("\n", $output));
        } finally {
            (new SkillFilesystemOperator())->remove($path);
        }
    }
}
