<?php

declare(strict_types=1);

namespace DecisionsLint\Cli;

use Closure;
use DecisionsLint\Http\Transport;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The public decisions-lint command boundary.
 *
 * It builds the Symfony Console application for a project directory and
 * runs it in-process, writing to the process streams or to one injected sink,
 * and reaching the provider over cURL or over one injected transport.
 */
final class Application
{
    /**
     * @param ?Closure(string): void $output receives standard output and standard error, or null for the process streams
     * @param ?Transport $transport reaches the provider, or null for cURL
     */
    public function __construct(private string $directory, private ?Closure $output = null, private ?Transport $transport = null)
    {
    }

    /**
     * Runs the command line and returns its exit code.
     *
     * @param list<string> $arguments arguments without the executable name
     */
    public function run(array $arguments): int
    {
        return $this->console()->doRun(new ArgvInput(array_merge(['decisions-lint'], $arguments)), $this->output());
    }

    /**
     * Builds the console application with the decisions-lint commands.
     */
    public function console(): DecisionsConsole
    {
        return new DecisionsConsole($this->directory, $this->transport);
    }

    /**
     * Returns the output the run writes to: the process streams, or the injected sink.
     */
    public function output(): OutputInterface
    {
        return $this->output === null ? new ConsoleOutput() : new ClosureOutput($this->output);
    }
}
