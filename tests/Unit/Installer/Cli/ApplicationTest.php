<?php

declare(strict_types=1);

namespace Tests\Unit\Installer\Cli;

use function file_put_contents;
use function implode;
use function is_dir;
use function mkdir;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function rmdir;

use SplFileInfo;

use function sys_get_temp_dir;

use Toolkit\Installer\Cli\Application;
use Toolkit\Installer\Cli\ApplicationHelpPrinter;
use Toolkit\Installer\Cli\ApplicationInstallRunner;
use Toolkit\Installer\Cli\CliArgumentParser;
use Toolkit\Installer\Cli\CliOutputWriter;
use Toolkit\Installer\Cli\Command\AgentSkillDirectoryDetector;
use Toolkit\Installer\Cli\Command\InstallCommand;
use Toolkit\Installer\Cli\Command\PackageSkillDirectoryScanner;
use Toolkit\Installer\Cli\Command\SkillFilesystemOperator;
use Toolkit\Installer\Cli\Command\SkillInstallationRunner;
use Toolkit\Installer\Cli\Command\SkillInstallationWriter;
use Toolkit\Installer\Cli\Command\SkillInstaller;
use Toolkit\Installer\PathNormalizer;
use Toolkit\Installer\RelativePathResolver;

use function uniqid;
use function unlink;

/**
 * @covers \Toolkit\Installer\Cli\Application
 * @uses \Toolkit\Installer\Cli\Command\AgentSkillDirectoryDetector
 * @uses \Toolkit\Installer\Cli\ApplicationHelpPrinter
 * @uses \Toolkit\Installer\Cli\ApplicationInstallRunner
 * @uses \Toolkit\Installer\Cli\CliArgumentParser
 * @uses \Toolkit\Installer\Cli\CliOutputWriter
 * @uses \Toolkit\Installer\Cli\Command\InstallCommand
 * @uses \Toolkit\Installer\Cli\Command\PackageSkillDirectoryScanner
 * @uses \Toolkit\Installer\PathNormalizer
 * @uses \Toolkit\Installer\RelativePathResolver
 * @uses \Toolkit\Installer\Cli\Command\SkillFilesystemOperator
 * @uses \Toolkit\Installer\Cli\Command\SkillInstallationRunner
 * @uses \Toolkit\Installer\Cli\Command\SkillInstallationWriter
 * @uses \Toolkit\Installer\Cli\Command\SkillInstaller
 */
#[CoversClass(Application::class)]
#[UsesClass(AgentSkillDirectoryDetector::class)]
#[UsesClass(ApplicationHelpPrinter::class)]
#[UsesClass(ApplicationInstallRunner::class)]
#[UsesClass(CliArgumentParser::class)]
#[UsesClass(CliOutputWriter::class)]
#[UsesClass(InstallCommand::class)]
#[UsesClass(PackageSkillDirectoryScanner::class)]
#[UsesClass(PathNormalizer::class)]
#[UsesClass(RelativePathResolver::class)]
#[UsesClass(SkillFilesystemOperator::class)]
#[UsesClass(SkillInstallationRunner::class)]
#[UsesClass(SkillInstallationWriter::class)]
#[UsesClass(SkillInstaller::class)]
final class ApplicationTest extends TestCase
{
    public function testRunHelpFlag(): void
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
            $app = new Application($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $app->run(['php-ai-toolkit', '--help']);

            self::assertSame(0, $exitCode);
            self::assertStringContainsString('Usage:', implode("\n", $output));
        } finally {
            $cleanup();
        }
    }

    public function testRunHelpShortFlag(): void
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
            $app = new Application($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $app->run(['php-ai-toolkit', '-h']);

            self::assertSame(0, $exitCode);
            self::assertStringContainsString('Usage:', implode("\n", $output));
        } finally {
            $cleanup();
        }
    }

    public function testRunVersionFlag(): void
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
            $app = new Application($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $app->run(['php-ai-toolkit', '--version']);

            self::assertSame(0, $exitCode);
            self::assertStringContainsString('php-ai-toolkit v', implode("\n", $output));
        } finally {
            $cleanup();
        }
    }

    public function testRunUnknownCommand(): void
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
            $app = new Application($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $app->run(['php-ai-toolkit', 'unknown']);

            self::assertSame(1, $exitCode);
            self::assertStringContainsString('[ERROR] Unknown command: unknown', implode("\n", $output));
        } finally {
            $cleanup();
        }
    }

    public function testRunDefaultsToInstall(): void
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
            file_put_contents($packageRoot . '/skills/test-skill/SKILL.md', 'test');

            $output = [];
            $app = new Application($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $app->run(['php-ai-toolkit']);

            self::assertSame(0, $exitCode);
            self::assertStringContainsString('Installing skills...', implode("\n", $output));
        } finally {
            $cleanup();
        }
    }

    public function testRunExplicitInstallCommand(): void
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
            file_put_contents($packageRoot . '/skills/test-skill/SKILL.md', 'test');

            $output = [];
            $app = new Application($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $app->run(['php-ai-toolkit', 'install']);

            self::assertSame(0, $exitCode);
            $fullOutput = implode("\n", $output);
            self::assertStringContainsString('Installing skills...', $fullOutput);
            self::assertStringContainsString('[OK] test-skill', $fullOutput);
        } finally {
            $cleanup();
        }
    }

    public function testRunInstallWithForceFlag(): void
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
            file_put_contents($packageRoot . '/skills/test-skill/SKILL.md', 'test');
            mkdir($projectRoot . '/.claude/skills/test-skill', 0755, true);

            $output = [];
            $app = new Application($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $app->run(['php-ai-toolkit', 'install', '--force']);

            self::assertSame(0, $exitCode);
            self::assertStringContainsString('[OK] test-skill', implode("\n", $output));
        } finally {
            $cleanup();
        }
    }

    public function testRunInstallWithCopyFlag(): void
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
            file_put_contents($packageRoot . '/skills/test-skill/SKILL.md', 'test');

            $output = [];
            $app = new Application($projectRoot, $packageRoot, static function (string $message) use (&$output): void {
                $output[] = $message;
            });

            $exitCode = $app->run(['php-ai-toolkit', 'install', '--copy']);

            self::assertSame(0, $exitCode);
            self::assertStringContainsString('(copied)', implode("\n", $output));
        } finally {
            $cleanup();
        }
    }
}
