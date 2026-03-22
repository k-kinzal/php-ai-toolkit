<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

use PhpStanAiRules\Cli\Command\InstallCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\TempDir;

use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_dir;
use function is_link;
use function mkdir;

#[CoversClass(InstallCommand::class)]
final class InstallCommandTest extends TestCase
{
    public function testExecuteNoSkillsDirectoryOutputsInfo(): void
    {
        $temp = new TempDir();
        $output = [];
        $command = new InstallCommand($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $command->execute();

        self::assertSame(0, $exitCode);
        self::assertSame(['[INFO] No skills directory found in package.'], $output);

        $temp->cleanup();
    }

    public function testExecuteEmptySkillsDirectoryOutputsInfo(): void
    {
        $temp = new TempDir();
        mkdir($temp->packageRoot . '/skills', 0755, true);

        $output = [];
        $command = new InstallCommand($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $command->execute();

        self::assertSame(0, $exitCode);
        self::assertSame(['[INFO] No skills found in package.'], $output);

        $temp->cleanup();
    }

    public function testExecuteGitkeepIsIgnored(): void
    {
        $temp = new TempDir();
        mkdir($temp->packageRoot . '/skills', 0755, true);
        file_put_contents($temp->packageRoot . '/skills/.gitkeep', '');

        $output = [];
        $command = new InstallCommand($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $command->execute();

        self::assertSame(0, $exitCode);
        self::assertSame(['[INFO] No skills found in package.'], $output);

        $temp->cleanup();
    }

    public function testExecuteCreatesSymlinksDefaultsToClaudeDir(): void
    {
        $temp = new TempDir();
        mkdir($temp->packageRoot . '/skills/test-skill', 0755, true);
        file_put_contents($temp->packageRoot . '/skills/test-skill/SKILL.md', 'test content');

        $output = [];
        $command = new InstallCommand($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $command->execute();

        self::assertSame(0, $exitCode);

        $linkPath = $temp->projectRoot . '/.claude/skills/test-skill';
        self::assertTrue(is_link($linkPath));
        self::assertFileExists($linkPath . '/SKILL.md');

        $temp->cleanup();
    }

    public function testExecuteCopyMode(): void
    {
        $temp = new TempDir();
        mkdir($temp->packageRoot . '/skills/test-skill', 0755, true);
        file_put_contents($temp->packageRoot . '/skills/test-skill/SKILL.md', 'test content');

        $output = [];
        $command = new InstallCommand($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $command->execute(copy: true);

        self::assertSame(0, $exitCode);

        $targetPath = $temp->projectRoot . '/.claude/skills/test-skill';
        self::assertFalse(is_link($targetPath));
        self::assertTrue(is_dir($targetPath));
        self::assertFileExists($targetPath . '/SKILL.md');
        self::assertSame('test content', file_get_contents($targetPath . '/SKILL.md'));

        $temp->cleanup();
    }

    public function testExecuteSkipsExistingSkill(): void
    {
        $temp = new TempDir();
        mkdir($temp->projectRoot . '/.claude/skills/test-skill', 0755, true);
        mkdir($temp->packageRoot . '/skills/test-skill', 0755, true);
        file_put_contents($temp->packageRoot . '/skills/test-skill/SKILL.md', 'test content');

        $output = [];
        $command = new InstallCommand($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $command->execute();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('[SKIP]', implode("\n", $output));

        $temp->cleanup();
    }

    public function testExecuteForceOverwritesExisting(): void
    {
        $temp = new TempDir();
        mkdir($temp->projectRoot . '/.claude/skills/test-skill', 0755, true);
        file_put_contents($temp->projectRoot . '/.claude/skills/test-skill/old-file.txt', 'old');
        mkdir($temp->packageRoot . '/skills/test-skill', 0755, true);
        file_put_contents($temp->packageRoot . '/skills/test-skill/SKILL.md', 'test content');

        $output = [];
        $command = new InstallCommand($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $command->execute(force: true);

        self::assertSame(0, $exitCode);

        $targetDir = $temp->projectRoot . '/.claude/skills/test-skill';
        self::assertTrue(is_link($targetDir));
        self::assertFileDoesNotExist($targetDir . '/old-file.txt');

        $temp->cleanup();
    }

    public function testExecuteForceOverwritesExistingSymlink(): void
    {
        $temp = new TempDir();
        mkdir($temp->projectRoot . '/.claude/skills', 0755, true);
        symlink('/nonexistent', $temp->projectRoot . '/.claude/skills/test-skill');
        mkdir($temp->packageRoot . '/skills/test-skill', 0755, true);
        file_put_contents($temp->packageRoot . '/skills/test-skill/SKILL.md', 'test content');

        $output = [];
        $command = new InstallCommand($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $command->execute(force: true);

        self::assertSame(0, $exitCode);
        self::assertTrue(is_link($temp->projectRoot . '/.claude/skills/test-skill'));
        self::assertFileExists($temp->projectRoot . '/.claude/skills/test-skill/SKILL.md');

        $temp->cleanup();
    }

    public function testExecuteInstallsMultipleSkills(): void
    {
        $temp = new TempDir();
        mkdir($temp->packageRoot . '/skills/skill-a', 0755, true);
        file_put_contents($temp->packageRoot . '/skills/skill-a/SKILL.md', 'a');
        mkdir($temp->packageRoot . '/skills/skill-b', 0755, true);
        file_put_contents($temp->packageRoot . '/skills/skill-b/SKILL.md', 'b');

        $output = [];
        $command = new InstallCommand($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $command->execute();

        self::assertSame(0, $exitCode);
        self::assertTrue(is_link($temp->projectRoot . '/.claude/skills/skill-a'));
        self::assertTrue(is_link($temp->projectRoot . '/.claude/skills/skill-b'));
        self::assertStringContainsString('2 skill(s) installed', implode("\n", $output));

        $temp->cleanup();
    }

    public function testExecuteCreatesTargetDirectory(): void
    {
        $temp = new TempDir();
        mkdir($temp->packageRoot . '/skills/test-skill', 0755, true);
        file_put_contents($temp->packageRoot . '/skills/test-skill/SKILL.md', 'test content');

        self::assertDirectoryDoesNotExist($temp->projectRoot . '/.claude/skills');

        $output = [];
        $command = new InstallCommand($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });
        $command->execute();

        self::assertDirectoryExists($temp->projectRoot . '/.claude/skills');

        $temp->cleanup();
    }

    public function testExecuteDetectsMultipleAgentDirectories(): void
    {
        $temp = new TempDir();
        mkdir($temp->projectRoot . '/.claude', 0755, true);
        mkdir($temp->projectRoot . '/.agents', 0755, true);
        mkdir($temp->packageRoot . '/skills/test-skill', 0755, true);
        file_put_contents($temp->packageRoot . '/skills/test-skill/SKILL.md', 'test content');

        $output = [];
        $command = new InstallCommand($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $command->execute();

        self::assertSame(0, $exitCode);
        self::assertTrue(is_link($temp->projectRoot . '/.claude/skills/test-skill'));
        self::assertTrue(is_link($temp->projectRoot . '/.agents/skills/test-skill'));
        self::assertStringContainsString('2 skill(s) installed', implode("\n", $output));

        $temp->cleanup();
    }

    public function testExecuteInstallsOnlyToDetectedAgents(): void
    {
        $temp = new TempDir();
        mkdir($temp->projectRoot . '/.continue', 0755, true);
        mkdir($temp->packageRoot . '/skills/test-skill', 0755, true);
        file_put_contents($temp->packageRoot . '/skills/test-skill/SKILL.md', 'test content');

        $output = [];
        $command = new InstallCommand($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $command->execute();

        self::assertSame(0, $exitCode);
        self::assertTrue(is_link($temp->projectRoot . '/.continue/skills/test-skill'));
        self::assertDirectoryDoesNotExist($temp->projectRoot . '/.claude/skills');

        $temp->cleanup();
    }

    public function testExecuteOutputIncludesAgentHeaders(): void
    {
        $temp = new TempDir();
        mkdir($temp->projectRoot . '/.claude', 0755, true);
        mkdir($temp->packageRoot . '/skills/test-skill', 0755, true);
        file_put_contents($temp->packageRoot . '/skills/test-skill/SKILL.md', 'test content');

        $output = [];
        $command = new InstallCommand($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $command->execute();

        $fullOutput = implode("\n", $output);
        self::assertStringContainsString('[.claude]', $fullOutput);

        $temp->cleanup();
    }
}
