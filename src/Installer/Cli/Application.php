<?php

declare(strict_types=1);

namespace PhpAiToolkit\Installer\Cli;

use function array_shift;

use Closure;

use function fwrite;

use const PHP_EOL;

use PhpAiToolkit\Installer\Cli\Command\InstallCommand;

use function sprintf;

use const STDOUT;

/**
 * CLI application for php-ai-toolkit.
 *
 * Dispatches subcommands and handles global flags.
 */
final class Application
{
    private const VERSION = '1.0.0';

    /** @var Closure(string): void */
    private Closure $output;

    /**
     * @param Closure(string): void|null $output writer function for CLI output (defaults to STDOUT)
     */
    public function __construct(
        private readonly string $projectRoot,
        private readonly string $packageRoot,
        ?Closure $output = null,
    ) {
        $this->output = $output ?? static function (string $message): void {
            fwrite(STDOUT, $message . PHP_EOL);
        };
    }

    /**
     * Runs the CLI application.
     *
     * Parses arguments, dispatches to the appropriate command,
     * and returns an exit code.
     *
     * @param list<string> $argv command-line arguments
     *
     * @return int exit code (0 = success, 1 = error)
     */
    public function run(array $argv): int
    {
        array_shift($argv);

        $arguments = $this->parseArguments($argv);
        if ($arguments['help']) {
            $this->printHelp();

            return 0;
        }

        if ($arguments['version']) {
            $this->write(sprintf('php-ai-toolkit v%s', self::VERSION));

            return 0;
        }

        $command = $arguments['command'] ?? 'install';
        if ($command === 'install') {
            return $this->runInstall($arguments['force'], $arguments['copy']);
        }

        $this->write(sprintf('[ERROR] Unknown command: %s', $command));
        $this->printHelp();

        return 1;
    }

    /**
     * @param list<string> $argv
     * @return array{command: string|null, force: bool, copy: bool, help: bool, version: bool}
     */
    private function parseArguments(array $argv): array
    {
        $arguments = [
            'command' => null,
            'force' => false,
            'copy' => false,
            'help' => false,
            'version' => false,
        ];

        foreach ($argv as $arg) {
            $arguments = $this->parseArgument($arguments, $arg);
        }

        return $arguments;
    }

    /**
     * @param array{command: string|null, force: bool, copy: bool, help: bool, version: bool} $arguments
     * @return array{command: string|null, force: bool, copy: bool, help: bool, version: bool}
     */
    private function parseArgument(array $arguments, string $arg): array
    {
        if ($arg === '--help' || $arg === '-h') {
            $arguments['help'] = true;
        } elseif ($arg === '--version' || $arg === '-V') {
            $arguments['version'] = true;
        } elseif ($arg === '--force' || $arg === '-f') {
            $arguments['force'] = true;
        } elseif ($arg === '--copy') {
            $arguments['copy'] = true;
        } elseif (!str_starts_with($arg, '-') && $arguments['command'] === null) {
            $arguments['command'] = $arg;
        }

        return $arguments;
    }

    private function runInstall(bool $force, bool $copy): int
    {
        $this->write(sprintf('php-ai-toolkit v%s', self::VERSION));
        $this->write('');
        $this->write('Installing skills...');

        $installCommand = new InstallCommand(
            $this->projectRoot,
            $this->packageRoot,
            $this->output,
        );

        return $installCommand->execute($force, $copy);
    }

    private function printHelp(): void
    {
        $this->write(sprintf('php-ai-toolkit v%s', self::VERSION));
        $this->write('');
        $this->write('Usage: php-ai-toolkit [command] [options]');
        $this->write('');
        $this->write('Commands:');
        $this->write('  install          Install skills into the project (default)');
        $this->write('');
        $this->write('Options:');
        $this->write('  --force, -f      Overwrite existing skills');
        $this->write('  --copy           Copy files instead of creating symlinks');
        $this->write('  --help, -h       Show this help message');
        $this->write('  --version, -V    Show version');
    }

    private function write(string $message): void
    {
        ($this->output)($message);
    }
}
