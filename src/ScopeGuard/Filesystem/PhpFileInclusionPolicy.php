<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Filesystem;

use function fnmatch;

use PhpAiToolkit\ScopeGuard\Config\ScopeGuardConfig;

use function str_ends_with;

/**
 * Decides whether a discovered file belongs in ScopeGuard analysis.
 *
 * @visibility parent
 */
final class PhpFileInclusionPolicy
{
    /** @readonly */
    private ScopeGuardPathResolver $pathResolver;

    /**
     * Creates an inclusion policy from path resolution.
     */
    public function __construct(?ScopeGuardPathResolver $pathResolver = null)
    {
        $this->pathResolver = $pathResolver ?? new ScopeGuardPathResolver();
    }

    /**
     * Reports whether the path is a non-excluded PHP file.
     */
    public function includes(ScopeGuardConfig $config, string $path): bool
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
