<?php

declare(strict_types=1);

namespace PhpAiToolkit\Installer\Cli;

use function array_shift;

use Closure;

use function sprintf;

/**
 * CLI application for php-ai-toolkit.
 *
 * Dispatches subcommands and handles global flags.
 */
final class Application
{
    private const VERSION = '1.0.0';

    /** @readonly */
    private CliOutputWriter $writer;

    /** @readonly */
    private CliArgumentParser $argumentParser;

    /** @readonly */
    private ApplicationHelpPrinter $helpPrinter;

    /** @readonly */
    private ApplicationInstallRunner $installRunner;

    /**
     * @param Closure(string): void|null $output writer function for CLI output (defaults to STDOUT)
     */
    public function __construct(
        /** @readonly */
        private string $projectRoot,
        /** @readonly */
        private string $packageRoot,
        ?Closure $output = null,
        ?CliArgumentParser $argumentParser = null,
        ?ApplicationHelpPrinter $helpPrinter = null,
        ?ApplicationInstallRunner $installRunner = null,
    ) {
        $this->writer = new CliOutputWriter($output);
        $this->argumentParser = $argumentParser ?? new CliArgumentParser();
        $this->helpPrinter = $helpPrinter ?? new ApplicationHelpPrinter($this->writer, self::VERSION);
        $this->installRunner = $installRunner ?? new ApplicationInstallRunner($this->projectRoot, $this->packageRoot, $this->writer, self::VERSION);
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

        $arguments = $this->argumentParser->parse($argv);
        if ($arguments['help']) {
            $this->helpPrinter->print();

            return 0;
        }

        if ($arguments['version']) {
            $this->writer->write(sprintf('php-ai-toolkit v%s', self::VERSION));

            return 0;
        }

        $command = $arguments['command'] ?? 'install';
        if ($command === 'install') {
            return $this->installRunner->run($arguments['force'], $arguments['copy']);
        }

        $this->writer->write(sprintf('[ERROR] Unknown command: %s', $command));
        $this->helpPrinter->print();

        return 1;
    }
}
