<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Filesystem;

use function fnmatch;

use PhpAiToolkit\Doctest\Config\DoctestConfig;

use function str_ends_with;

/**
 * Decides whether a discovered file is scanned for examples.
 *
 * @visibility parent
 */
final class PhpFileInclusionPolicy
{
    /** @readonly */
    private DoctestPathResolver $pathResolver;

    /**
     * Creates an inclusion policy from path resolution.
     */
    public function __construct(?DoctestPathResolver $pathResolver = null)
    {
        $this->pathResolver = $pathResolver ?? new DoctestPathResolver();
    }

    /**
     * Reports whether the path is a non-excluded PHP file.
     */
    public function includes(DoctestConfig $config, string $path): bool
    {
        $relativePath = $this->pathResolver->relative($config->root, $path);
        if (!str_ends_with($relativePath, '.php')) {
            return false;
        }

        foreach ($config->exclude as $pattern) {
            if (fnmatch($pattern, $relativePath)) {
                return false;
            }
        }

        return true;
    }
}
