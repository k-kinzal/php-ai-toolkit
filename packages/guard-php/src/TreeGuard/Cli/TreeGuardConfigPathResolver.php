<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Cli;

/**
 * Resolves TreeGuard config paths relative to the working directory.
 */
final class TreeGuardConfigPathResolver
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
