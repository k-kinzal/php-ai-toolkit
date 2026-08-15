<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Cli;

use function rtrim;
use function str_starts_with;

/**
 * Resolves the configured doc.yaml path against the working directory.
 */
final class DocGenConfigPathResolver
{
    /**
     * Resolves a possibly relative config path.
     */
    public function resolve(string $workingDirectory, string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return rtrim($workingDirectory, '/') . '/' . $path;
    }
}
