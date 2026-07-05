<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Cli;

use Closure;

use function fwrite;

use const STDERR;
use const STDOUT;

/**
 * Writes LocGuard CLI output to stdout and stderr.
 */
final class LocGuardOutputWriter
{
    /** @var Closure(string): void */
    private Closure $stdout;

    /** @var Closure(string): void */
    private Closure $stderr;

    /**
     * Creates a writer from optional output closures.
     *
     * @param Closure(string): void|null $stdout
     * @param Closure(string): void|null $stderr
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
     * Writes to standard output.
     */
    public function write(string $message): void
    {
        ($this->stdout)($message);
    }

    /**
     * Writes to standard error.
     */
    public function writeError(string $message): void
    {
        ($this->stderr)($message);
    }
}
