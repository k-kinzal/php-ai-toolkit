<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use PhpStanAiRules\Cli\Application;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\TempDir;

use function file_put_contents;
use function implode;
use function mkdir;

#[CoversClass(Application::class)]
final class ApplicationTest extends TestCase
{
    public function testRunHelpFlag(): void
    {
        $temp = new TempDir();
        $output = [];
        $app = new Application($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $app->run(['php-ai-toolkit', '--help']);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Usage:', implode("\n", $output));

        $temp->cleanup();
    }

    public function testRunHelpShortFlag(): void
    {
        $temp = new TempDir();
        $output = [];
        $app = new Application($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $app->run(['php-ai-toolkit', '-h']);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Usage:', implode("\n", $output));

        $temp->cleanup();
    }

    public function testRunVersionFlag(): void
    {
        $temp = new TempDir();
        $output = [];
        $app = new Application($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $app->run(['php-ai-toolkit', '--version']);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('php-ai-toolkit v', implode("\n", $output));

        $temp->cleanup();
    }

    public function testRunUnknownCommand(): void
    {
        $temp = new TempDir();
        $output = [];
        $app = new Application($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $app->run(['php-ai-toolkit', 'unknown']);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('[ERROR] Unknown command: unknown', implode("\n", $output));

        $temp->cleanup();
    }

    public function testRunDefaultsToInstall(): void
    {
        $temp = new TempDir();
        mkdir($temp->packageRoot . '/skills/test-skill', 0755, true);
        file_put_contents($temp->packageRoot . '/skills/test-skill/SKILL.md', 'test');

        $output = [];
        $app = new Application($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $app->run(['php-ai-toolkit']);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Installing skills...', implode("\n", $output));

        $temp->cleanup();
    }

    public function testRunExplicitInstallCommand(): void
    {
        $temp = new TempDir();
        mkdir($temp->packageRoot . '/skills/test-skill', 0755, true);
        file_put_contents($temp->packageRoot . '/skills/test-skill/SKILL.md', 'test');

        $output = [];
        $app = new Application($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $app->run(['php-ai-toolkit', 'install']);

        self::assertSame(0, $exitCode);
        $fullOutput = implode("\n", $output);
        self::assertStringContainsString('Installing skills...', $fullOutput);
        self::assertStringContainsString('[OK] test-skill', $fullOutput);

        $temp->cleanup();
    }

    public function testRunInstallWithForceFlag(): void
    {
        $temp = new TempDir();
        mkdir($temp->packageRoot . '/skills/test-skill', 0755, true);
        file_put_contents($temp->packageRoot . '/skills/test-skill/SKILL.md', 'test');
        mkdir($temp->projectRoot . '/.claude/skills/test-skill', 0755, true);

        $output = [];
        $app = new Application($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $app->run(['php-ai-toolkit', 'install', '--force']);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('[OK] test-skill', implode("\n", $output));

        $temp->cleanup();
    }

    public function testRunInstallWithCopyFlag(): void
    {
        $temp = new TempDir();
        mkdir($temp->packageRoot . '/skills/test-skill', 0755, true);
        file_put_contents($temp->packageRoot . '/skills/test-skill/SKILL.md', 'test');

        $output = [];
        $app = new Application($temp->projectRoot, $temp->packageRoot, static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $exitCode = $app->run(['php-ai-toolkit', 'install', '--copy']);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('(copied)', implode("\n", $output));

        $temp->cleanup();
    }
}
