<?php

declare(strict_types=1);

namespace PhpStanAiRules\Cli;

use Closure;
use PhpStanAiRules\Cli\Command\InstallCommand;

use function array_shift;
use function fwrite;
use function in_array;
use function sprintf;

use const PHP_EOL;
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

        $command = null;
        $force = false;
        $copy = false;

        foreach ($argv as $arg) {
            if ($arg === '--help' || $arg === '-h') {
                $this->printHelp();

                return 0;
            }

            if ($arg === '--version' || $arg === '-V') {
                $this->write(sprintf('php-ai-toolkit v%s', self::VERSION));

                return 0;
            }

            if ($arg === '--force' || $arg === '-f') {
                $force = true;

                continue;
            }

            if ($arg === '--copy') {
                $copy = true;

                continue;
            }

            if (!str_starts_with($arg, '-') && $command === null) {
                $command = $arg;
            }
        }

        if ($command === null) {
            $command = 'install';
        }

        if ($command === 'install') {
            return $this->runInstall($force, $copy);
        }

        $this->write(sprintf('[ERROR] Unknown command: %s', $command));
        $this->printHelp();

        return 1;
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
