<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Cli;

use Closure;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI entry point for DocGen.
 *
 * It builds the Symfony Console application for a project directory and
 * runs it in-process, writing to the process streams or to injected sinks.
 */
final class Application
{
    /** @readonly */
    private string $workingDirectory;

    /** @readonly */
    private ?Closure $stdout;

    /** @readonly */
    private ?Closure $stderr;

    /**
     * Creates the DocGen CLI application for a project working directory.
     *
     * @param ?Closure(string): void $stdout receives standard output, or null for the process stream
     * @param ?Closure(string): void $stderr receives standard error, or null for the process stream
     */
    public function __construct(string $workingDirectory, ?Closure $stdout = null, ?Closure $stderr = null)
    {
        $this->workingDirectory = $workingDirectory;
        $this->stdout = $stdout;
        $this->stderr = $stderr;
    }

    /**
     * Runs the CLI with raw process arguments.
     *
     * @param list<string> $argv the arguments, starting with the executable name
     */
    public function run(array $argv): int
    {
        return $this->console()->doRun(new ArgvInput($argv), $this->output());
    }

    /**
     * Builds the console application that runs the docgen command.
     */
    public function console(): DocGenConsole
    {
        return new DocGenConsole($this->workingDirectory);
    }

    /**
     * Returns the output the run writes to: the process streams, or the injected sinks.
     */
    public function output(): OutputInterface
    {
        if ($this->stdout === null && $this->stderr === null) {
            return new ConsoleOutput();
        }

        $writer = new DocGenOutputWriter($this->stdout, $this->stderr);

        return new ClosureOutput(
            static function (string $message) use ($writer): void {
                $writer->write($message);
            },
            new ClosureOutput(static function (string $message) use ($writer): void {
                $writer->writeError($message);
            }),
        );
    }
}
