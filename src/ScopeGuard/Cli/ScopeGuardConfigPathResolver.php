<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Cli;

use function str_starts_with;

/**
 * Resolves ScopeGuard config paths relative to the working directory.
 *
 * @visibility namespace
 */
final class ScopeGuardConfigPathResolver
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
