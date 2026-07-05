<?php

declare(strict_types=1);

namespace PhpAiToolkit\Installer\Cli;

use Closure;

use function fwrite;

use const PHP_EOL;
use const STDOUT;

/**
 * Writes CLI output to either a provided closure or STDOUT.
 */
final class CliOutputWriter
{
    /** @var Closure(string): void */
    private Closure $output;

    /**
     * @param Closure(string): void|null $output writer function for CLI output
     */
    public function __construct(?Closure $output = null)
    {
        $this->output = $output ?? static function (string $message): void {
            fwrite(STDOUT, $message . PHP_EOL);
        };
    }

    /**
     * Writes one CLI output line.
     */
    public function write(string $message): void
    {
        ($this->output)($message);
    }
}
