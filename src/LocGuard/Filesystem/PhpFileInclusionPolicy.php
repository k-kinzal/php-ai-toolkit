<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Filesystem;

use function fnmatch;

use PhpAiToolkit\LocGuard\Config\LocGuardConfig;

use function str_ends_with;

/**
 * Decides whether a discovered file belongs in LocGuard analysis.
 */
final class PhpFileInclusionPolicy
{
    /**
     * Creates an inclusion policy from path resolution.
     */
    public function __construct(
        private readonly LocGuardPathResolver $pathResolver = new LocGuardPathResolver(),
    ) {
    }

    /**
     * Reports whether the path is a non-excluded PHP file.
     */
    public function includes(LocGuardConfig $config, string $path): bool
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
