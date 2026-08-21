<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Cli;

use function str_starts_with;

/**
 * Resolves doctest config paths relative to the working directory.
 *
 * @visibility namespace
 */
final class DoctestConfigPathResolver
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
