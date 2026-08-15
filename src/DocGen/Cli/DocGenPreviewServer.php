<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Cli;

use Closure;

use function escapeshellarg;
use function is_int;
use function passthru;

use const PHP_BINARY;

use function sprintf;

/**
 * Serves the generated site with the PHP built-in web server.
 */
final class DocGenPreviewServer
{
    /** @readonly */
    private Closure $launcher;

    /**
     * Creates a preview server with an injectable process launcher.
     */
    public function __construct(?Closure $launcher = null)
    {
        $this->launcher = $launcher ?? static function (string $command): int {
            $exitCode = 0;
            passthru($command, $exitCode);

            return $exitCode;
        };
    }

    /**
     * Serves one document root at the given address until interrupted.
     */
    public function serve(string $documentRoot, string $address): int
    {
        $exitCode = ($this->launcher)($this->command($documentRoot, $address));

        return is_int($exitCode) ? $exitCode : 0;
    }

    /**
     * Builds the built-in web server command line.
     */
    public function command(string $documentRoot, string $address): string
    {
        return sprintf('%s -S %s -t %s', escapeshellarg(PHP_BINARY), escapeshellarg($address), escapeshellarg($documentRoot));
    }
}
