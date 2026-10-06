<?php

declare(strict_types=1);

namespace Guard\Collect\Tree\Filesystem;

use function fnmatch;

use Guard\Config\Tree\StructureConfig;

/**
 * Decides whether a discovered file or directory belongs in directory analysis.
 *
 * Unlike source metric, every file type is included; only the configured exclude
 * globs remove entries, and an excluded directory prunes its whole subtree.
 */
final class PathInclusionPolicy
{
    /**
     * Reports whether the relative path survives the configured exclude globs.
     */
    public function includes(StructureConfig $config, string $relativePath): bool
    {
        foreach ($config->exclude as $pattern) {
            if (fnmatch($pattern, $relativePath)) {
                return false;
            }
        }

        return true;
    }
}
