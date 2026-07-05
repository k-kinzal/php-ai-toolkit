<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Cli;

/**
 * Resolves LocGuard config paths relative to the working directory.
 */
final class LocGuardConfigPathResolver
{
    /**
     * Returns an absolute config path.
     */
    public function resolve(string $workingDirectory, string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return $workingDirectory . '/' . $path;
    }
}
