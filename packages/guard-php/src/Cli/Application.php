<?php

declare(strict_types=1);

namespace Guard\Cli;

use Closure;
use Guard\Execution\Registry;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The public guard command boundary.
 *
 * It builds the Symfony Console application for a project directory and
 * runs it in-process, writing to the process streams or to one injected sink.
 */
final class Application
{
    /**
     * @param ?Closure(string): void $output receives standard output and standard error, or null for the process streams
     */
    public function __construct(private string $directory, private ?Closure $output = null, private ?Registry $registry = null)
    {
    }
    /**
     * @param list<string> $arguments arguments without the executable name
     */
    public function run(array $arguments): int
    {
        return $this->console()->doRun(new ArgvInput(array_merge(['guard'], $arguments)), $this->output());
    }
    /**
     * Builds the console application with the guard commands.
     */
    public function console(): GuardConsole
    {
        return new GuardConsole($this->directory, $this->registry);
    }
    /**
     * Returns the output the run writes to: the process streams, or the injected sink.
     */
    public function output(): OutputInterface
    {
        return $this->output === null ? new ConsoleOutput() : new ClosureOutput($this->output);
    }
}
