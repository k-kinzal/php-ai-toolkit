<?php

declare(strict_types=1);

namespace Tests\Unit\Installer\Cli;

use function file_put_contents;
use function implode;
use function mkdir;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function sys_get_temp_dir;

use Toolkit\Installer\Cli\ApplicationInstallRunner;
use Toolkit\Installer\Cli\CliOutputWriter;
use Toolkit\Installer\Cli\Command\AgentSkillDirectoryDetector;
use Toolkit\Installer\Cli\Command\InstallCommand;
use Toolkit\Installer\Cli\Command\PackageSkillDirectoryScanner;
use Toolkit\Installer\Cli\Command\SkillFilesystemOperator;
use Toolkit\Installer\Cli\Command\SkillInstallationRunner;
use Toolkit\Installer\Cli\Command\SkillInstallationWriter;
use Toolkit\Installer\Cli\Command\SkillInstaller;
use Toolkit\Installer\RelativePathResolver;

use function uniqid;

/**
 * @covers \Toolkit\Installer\Cli\ApplicationInstallRunner
 * @uses \Toolkit\Installer\Cli\Command\AgentSkillDirectoryDetector
 * @uses \Toolkit\Installer\Cli\CliOutputWriter
 * @uses \Toolkit\Installer\Cli\Command\InstallCommand
 * @uses \Toolkit\Installer\Cli\Command\PackageSkillDirectoryScanner
 * @uses \Toolkit\Installer\RelativePathResolver
 * @uses \Toolkit\Installer\Cli\Command\SkillFilesystemOperator
 * @uses \Toolkit\Installer\Cli\Command\SkillInstallationRunner
 * @uses \Toolkit\Installer\Cli\Command\SkillInstallationWriter
 * @uses \Toolkit\Installer\Cli\Command\SkillInstaller
 */
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
