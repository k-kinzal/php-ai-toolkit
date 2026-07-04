<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_dir;
use function is_link;
use function mkdir;

use PhpStanAiRules\Cli\Command\InstallCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function rmdir;

use SplFileInfo;

use function symlink;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

#[CoversClass(InstallCommand::class)]
final class InstallCommandTest extends TestCase
{
    public function testExecuteNoSkillsDirectoryOutputsInfo(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        $projectRoot = $path . '/project';
        $packageRoot = $path . '/package';
        mkdir($projectRoot, 0755, true);
        mkdir($packageRoot, 0755, true);
        $cleanup = static function () use ($path): void {
            if (!is_dir($path)) {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            /** @var SplFileInfo $item */
            foreach ($iterator as $item) {
                if ($item->isLink() || !$item->isDir()) {
                    unlink($item->getPathname());
                } else {
                    rmdir($item->getPathname());
                }
            }

            rmdir($path);
        };

        try {
            $output = [];
            $command = new InstallCommand($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $command->execute();

            self::assertSame(0, $exitCode);
            self::assertSame(['[INFO] No skills directory found in package.'], $output);
        } finally {
            $cleanup();
        }
    }

    public function testExecuteEmptySkillsDirectoryOutputsInfo(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        $projectRoot = $path . '/project';
        $packageRoot = $path . '/package';
        mkdir($projectRoot, 0755, true);
        mkdir($packageRoot, 0755, true);
        $cleanup = static function () use ($path): void {
            if (!is_dir($path)) {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            /** @var SplFileInfo $item */
            foreach ($iterator as $item) {
                if ($item->isLink() || !$item->isDir()) {
                    unlink($item->getPathname());
                } else {
                    rmdir($item->getPathname());
                }
            }

            rmdir($path);
        };

        try {
            mkdir($packageRoot . '/skills', 0755, true);

            $output = [];
            $command = new InstallCommand($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $command->execute();

            self::assertSame(0, $exitCode);
            self::assertSame(['[INFO] No skills found in package.'], $output);
        } finally {
            $cleanup();
        }
    }

    public function testExecuteGitkeepIsIgnored(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        $projectRoot = $path . '/project';
        $packageRoot = $path . '/package';
        mkdir($projectRoot, 0755, true);
        mkdir($packageRoot, 0755, true);
        $cleanup = static function () use ($path): void {
            if (!is_dir($path)) {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            /** @var SplFileInfo $item */
            foreach ($iterator as $item) {
                if ($item->isLink() || !$item->isDir()) {
                    unlink($item->getPathname());
                } else {
                    rmdir($item->getPathname());
                }
            }

            rmdir($path);
        };

        try {
            mkdir($packageRoot . '/skills', 0755, true);
            file_put_contents($packageRoot . '/skills/.gitkeep', '');

            $output = [];
            $command = new InstallCommand($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $command->execute();

            self::assertSame(0, $exitCode);
            self::assertSame(['[INFO] No skills found in package.'], $output);
        } finally {
            $cleanup();
        }
    }

    public function testExecuteCreatesSymlinksDefaultsToClaudeDir(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        $projectRoot = $path . '/project';
        $packageRoot = $path . '/package';
        mkdir($projectRoot, 0755, true);
        mkdir($packageRoot, 0755, true);
        $cleanup = static function () use ($path): void {
            if (!is_dir($path)) {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            /** @var SplFileInfo $item */
            foreach ($iterator as $item) {
                if ($item->isLink() || !$item->isDir()) {
                    unlink($item->getPathname());
                } else {
                    rmdir($item->getPathname());
                }
            }

            rmdir($path);
        };

        try {
            mkdir($packageRoot . '/skills/test-skill', 0755, true);
            file_put_contents($packageRoot . '/skills/test-skill/SKILL.md', 'test content');

            $output = [];
            $command = new InstallCommand($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $command->execute();

            self::assertSame(0, $exitCode);

            $linkPath = $projectRoot . '/.claude/skills/test-skill';
            self::assertTrue(is_link($linkPath));
            self::assertFileExists($linkPath . '/SKILL.md');
        } finally {
            $cleanup();
        }
    }

    public function testExecuteCopyMode(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        $projectRoot = $path . '/project';
        $packageRoot = $path . '/package';
        mkdir($projectRoot, 0755, true);
        mkdir($packageRoot, 0755, true);
        $cleanup = static function () use ($path): void {
            if (!is_dir($path)) {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            /** @var SplFileInfo $item */
            foreach ($iterator as $item) {
                if ($item->isLink() || !$item->isDir()) {
                    unlink($item->getPathname());
                } else {
                    rmdir($item->getPathname());
                }
            }

            rmdir($path);
        };

        try {
            mkdir($packageRoot . '/skills/test-skill', 0755, true);
            file_put_contents($packageRoot . '/skills/test-skill/SKILL.md', 'test content');

            $output = [];
            $command = new InstallCommand($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $command->execute(copy: true);

            self::assertSame(0, $exitCode);

            $targetPath = $projectRoot . '/.claude/skills/test-skill';
            self::assertFalse(is_link($targetPath));
            self::assertTrue(is_dir($targetPath));
            self::assertFileExists($targetPath . '/SKILL.md');
            self::assertSame('test content', file_get_contents($targetPath . '/SKILL.md'));
        } finally {
            $cleanup();
        }
    }

    public function testExecuteSkipsExistingSkill(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        $projectRoot = $path . '/project';
        $packageRoot = $path . '/package';
        mkdir($projectRoot, 0755, true);
        mkdir($packageRoot, 0755, true);
        $cleanup = static function () use ($path): void {
            if (!is_dir($path)) {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            /** @var SplFileInfo $item */
            foreach ($iterator as $item) {
                if ($item->isLink() || !$item->isDir()) {
                    unlink($item->getPathname());
                } else {
                    rmdir($item->getPathname());
                }
            }

            rmdir($path);
        };

        try {
            mkdir($projectRoot . '/.claude/skills/test-skill', 0755, true);
            mkdir($packageRoot . '/skills/test-skill', 0755, true);
            file_put_contents($packageRoot . '/skills/test-skill/SKILL.md', 'test content');

            $output = [];
            $command = new InstallCommand($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $command->execute();

            self::assertSame(0, $exitCode);
            self::assertStringContainsString('[SKIP]', implode("\n", $output));
        } finally {
            $cleanup();
        }
    }

    public function testExecuteForceOverwritesExisting(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        $projectRoot = $path . '/project';
        $packageRoot = $path . '/package';
        mkdir($projectRoot, 0755, true);
        mkdir($packageRoot, 0755, true);
        $cleanup = static function () use ($path): void {
            if (!is_dir($path)) {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            /** @var SplFileInfo $item */
            foreach ($iterator as $item) {
                if ($item->isLink() || !$item->isDir()) {
                    unlink($item->getPathname());
                } else {
                    rmdir($item->getPathname());
                }
            }

            rmdir($path);
        };

        try {
            mkdir($projectRoot . '/.claude/skills/test-skill', 0755, true);
            file_put_contents($projectRoot . '/.claude/skills/test-skill/old-file.txt', 'old');
            mkdir($packageRoot . '/skills/test-skill', 0755, true);
            file_put_contents($packageRoot . '/skills/test-skill/SKILL.md', 'test content');

            $output = [];
            $command = new InstallCommand($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $command->execute(force: true);

            self::assertSame(0, $exitCode);

            $targetDir = $projectRoot . '/.claude/skills/test-skill';
            self::assertTrue(is_link($targetDir));
            self::assertFileDoesNotExist($targetDir . '/old-file.txt');
        } finally {
            $cleanup();
        }
    }

    public function testExecuteForceOverwritesExistingSymlink(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        $projectRoot = $path . '/project';
        $packageRoot = $path . '/package';
        mkdir($projectRoot, 0755, true);
        mkdir($packageRoot, 0755, true);
        $cleanup = static function () use ($path): void {
            if (!is_dir($path)) {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            /** @var SplFileInfo $item */
            foreach ($iterator as $item) {
                if ($item->isLink() || !$item->isDir()) {
                    unlink($item->getPathname());
                } else {
                    rmdir($item->getPathname());
                }
            }

            rmdir($path);
        };

        try {
            mkdir($projectRoot . '/.claude/skills', 0755, true);
            symlink('/nonexistent', $projectRoot . '/.claude/skills/test-skill');
            mkdir($packageRoot . '/skills/test-skill', 0755, true);
            file_put_contents($packageRoot . '/skills/test-skill/SKILL.md', 'test content');

            $output = [];
            $command = new InstallCommand($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $command->execute(force: true);

            self::assertSame(0, $exitCode);
            self::assertTrue(is_link($projectRoot . '/.claude/skills/test-skill'));
            self::assertFileExists($projectRoot . '/.claude/skills/test-skill/SKILL.md');
        } finally {
            $cleanup();
        }
    }

    public function testExecuteInstallsMultipleSkills(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        $projectRoot = $path . '/project';
        $packageRoot = $path . '/package';
        mkdir($projectRoot, 0755, true);
        mkdir($packageRoot, 0755, true);
        $cleanup = static function () use ($path): void {
            if (!is_dir($path)) {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            /** @var SplFileInfo $item */
            foreach ($iterator as $item) {
                if ($item->isLink() || !$item->isDir()) {
                    unlink($item->getPathname());
                } else {
                    rmdir($item->getPathname());
                }
            }

            rmdir($path);
        };

        try {
            mkdir($packageRoot . '/skills/skill-a', 0755, true);
            file_put_contents($packageRoot . '/skills/skill-a/SKILL.md', 'a');
            mkdir($packageRoot . '/skills/skill-b', 0755, true);
            file_put_contents($packageRoot . '/skills/skill-b/SKILL.md', 'b');

            $output = [];
            $command = new InstallCommand($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $command->execute();

            self::assertSame(0, $exitCode);
            self::assertTrue(is_link($projectRoot . '/.claude/skills/skill-a'));
            self::assertTrue(is_link($projectRoot . '/.claude/skills/skill-b'));
            self::assertStringContainsString('2 skill(s) installed', implode("\n", $output));
        } finally {
            $cleanup();
        }
    }

    public function testExecuteCreatesTargetDirectory(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        $projectRoot = $path . '/project';
        $packageRoot = $path . '/package';
        mkdir($projectRoot, 0755, true);
        mkdir($packageRoot, 0755, true);
        $cleanup = static function () use ($path): void {
            if (!is_dir($path)) {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            /** @var SplFileInfo $item */
            foreach ($iterator as $item) {
                if ($item->isLink() || !$item->isDir()) {
                    unlink($item->getPathname());
                } else {
                    rmdir($item->getPathname());
                }
            }

            rmdir($path);
        };

        try {
            mkdir($packageRoot . '/skills/test-skill', 0755, true);
            file_put_contents($packageRoot . '/skills/test-skill/SKILL.md', 'test content');

            self::assertDirectoryDoesNotExist($projectRoot . '/.claude/skills');

            $output = [];
            $command = new InstallCommand($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });
            $command->execute();

            self::assertDirectoryExists($projectRoot . '/.claude/skills');
        } finally {
            $cleanup();
        }
    }

    public function testExecuteDetectsMultipleAgentDirectories(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        $projectRoot = $path . '/project';
        $packageRoot = $path . '/package';
        mkdir($projectRoot, 0755, true);
        mkdir($packageRoot, 0755, true);
        $cleanup = static function () use ($path): void {
            if (!is_dir($path)) {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            /** @var SplFileInfo $item */
            foreach ($iterator as $item) {
                if ($item->isLink() || !$item->isDir()) {
                    unlink($item->getPathname());
                } else {
                    rmdir($item->getPathname());
                }
            }

            rmdir($path);
        };

        try {
            mkdir($projectRoot . '/.claude', 0755, true);
            mkdir($projectRoot . '/.agents', 0755, true);
            mkdir($packageRoot . '/skills/test-skill', 0755, true);
            file_put_contents($packageRoot . '/skills/test-skill/SKILL.md', 'test content');

            $output = [];
            $command = new InstallCommand($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $command->execute();

            self::assertSame(0, $exitCode);
            self::assertTrue(is_link($projectRoot . '/.claude/skills/test-skill'));
            self::assertTrue(is_link($projectRoot . '/.agents/skills/test-skill'));
            self::assertStringContainsString('2 skill(s) installed', implode("\n", $output));
        } finally {
            $cleanup();
        }
    }

    public function testExecuteInstallsOnlyToDetectedAgents(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        $projectRoot = $path . '/project';
        $packageRoot = $path . '/package';
        mkdir($projectRoot, 0755, true);
        mkdir($packageRoot, 0755, true);
        $cleanup = static function () use ($path): void {
            if (!is_dir($path)) {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            /** @var SplFileInfo $item */
            foreach ($iterator as $item) {
                if ($item->isLink() || !$item->isDir()) {
                    unlink($item->getPathname());
                } else {
                    rmdir($item->getPathname());
                }
            }

            rmdir($path);
        };

        try {
            mkdir($projectRoot . '/.continue', 0755, true);
            mkdir($packageRoot . '/skills/test-skill', 0755, true);
            file_put_contents($packageRoot . '/skills/test-skill/SKILL.md', 'test content');

            $output = [];
            $command = new InstallCommand($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $command->execute();

            self::assertSame(0, $exitCode);
            self::assertTrue(is_link($projectRoot . '/.continue/skills/test-skill'));
            self::assertDirectoryDoesNotExist($projectRoot . '/.claude/skills');
        } finally {
            $cleanup();
        }
    }

    public function testExecuteOutputIncludesAgentHeaders(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        $projectRoot = $path . '/project';
        $packageRoot = $path . '/package';
        mkdir($projectRoot, 0755, true);
        mkdir($packageRoot, 0755, true);
        $cleanup = static function () use ($path): void {
            if (!is_dir($path)) {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            /** @var SplFileInfo $item */
            foreach ($iterator as $item) {
                if ($item->isLink() || !$item->isDir()) {
                    unlink($item->getPathname());
                } else {
                    rmdir($item->getPathname());
                }
            }

            rmdir($path);
        };

        try {
            mkdir($projectRoot . '/.claude', 0755, true);
            mkdir($packageRoot . '/skills/test-skill', 0755, true);
            file_put_contents($packageRoot . '/skills/test-skill/SKILL.md', 'test content');

            $output = [];
            $command = new InstallCommand($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $command->execute();

            $fullOutput = implode("\n", $output);
            self::assertStringContainsString('[.claude]', $fullOutput);
        } finally {
            $cleanup();
        }
    }
}
