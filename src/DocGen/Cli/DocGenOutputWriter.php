<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Cli;

use Closure;

use function fwrite;

use const STDERR;
use const STDOUT;

/**
 * Writes CLI output through injectable stream closures.
 */
final class DocGenOutputWriter
{
    /** @readonly */
    private Closure $stdout;

    /** @readonly */
    private Closure $stderr;

    /**
     * Creates an output writer with optional stream overrides.
     */
    public function __construct(?Closure $stdout = null, ?Closure $stderr = null)
    {
        $this->stdout = $stdout ?? static function (string $message): void {
            fwrite(STDOUT, $message);
        };
        $this->stderr = $stderr ?? static function (string $message): void {
            fwrite(STDERR, $message);
        };
    }

    /**
     * Writes a message to standard output.
     */
    public function write(string $message): void
    {
        ($this->stdout)($message);
    }

    /**
     * Writes a message to standard error.
     */
    public function writeError(string $message): void
    {
        ($this->stderr)($message);
    }
}
