<?php

declare(strict_types=1);

namespace Guard\Cli;

use Closure;
use Guard\Policy\PolicyException;
use Override;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console output that hands every written message to a closure.
 *
 * It lets the console application write into the sinks a caller injected,
 * with standard error kept apart when the caller gives one.
 */
final class ClosureOutput extends Output implements ConsoleOutputInterface
{
    /** @readonly */
    private Closure $sink;

    private OutputInterface $errorOutput;

    /**
     * Creates an output that writes to a sink, and its errors to another output.
     *
     * @param Closure(string): void $sink receives every written message
     * @param ?OutputInterface $errorOutput receives the errors, or null to keep them in this output
     */
    public function __construct(Closure $sink, ?OutputInterface $errorOutput = null)
    {
        parent::__construct();
        $this->sink = $sink;
        $this->errorOutput = $errorOutput ?? $this;
    }

    /**
     * Returns the output the errors are written to.
     */
    public function getErrorOutput(): OutputInterface
    {
        return $this->errorOutput;
    }

    /**
     * Replaces the output the errors are written to.
     */
    public function setErrorOutput(OutputInterface $error): void
    {
        $this->errorOutput = $error;
    }

    /**
     * Refuses sections, which need a terminal stream to redraw.
     *
     * @throws PolicyException always
     */
    public function section(): ConsoleSectionOutput
    {
        throw new PolicyException('Output sections need a terminal stream; write to the console output instead.');
    }

    /**
     * Sets the verbosity of this output and of its error output.
     */
    #[Override]
    public function setVerbosity(int $level): void
    {
        parent::setVerbosity($level);
        if ($this->errorOutput !== $this) {
            $this->errorOutput->setVerbosity($level);
        }
    }

    /**
     * Turns decoration on or off for this output and its error output.
     */
    #[Override]
    public function setDecorated(bool $decorated): void
    {
        parent::setDecorated($decorated);
        if ($this->errorOutput !== $this) {
            $this->errorOutput->setDecorated($decorated);
        }
    }

    /**
     * Sets the formatter of this output and of its error output.
     */
    #[Override]
    public function setFormatter(OutputFormatterInterface $formatter): void
    {
        parent::setFormatter($formatter);
        if ($this->errorOutput !== $this) {
            $this->errorOutput->setFormatter($formatter);
        }
    }

    /**
     * Hands one formatted message to the sink.
     */
    #[Override]
    protected function doWrite(string $message, bool $newline): void
    {
        ($this->sink)($newline ? $message . "\n" : $message);
    }
}
