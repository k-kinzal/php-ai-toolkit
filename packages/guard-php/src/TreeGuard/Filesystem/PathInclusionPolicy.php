<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Filesystem;

use function fnmatch;

use Toolkit\TreeGuard\Config\TreeGuardConfig;

/**
 * Decides whether a discovered file or directory belongs in TreeGuard analysis.
 *
 * Unlike LocGuard, every file type is included; only the configured exclude
 * globs remove entries, and an excluded directory prunes its whole subtree.
 */
final class PathInclusionPolicy
{
    /**
     * Reports whether the relative path survives the configured exclude globs.
     */
    public function includes(TreeGuardConfig $config, string $relativePath): bool
    {
        foreach ($config->exclude as $pattern) {
            if (fnmatch($pattern, $relativePath)) {
                return false;
            }
        }

        return true;
    }
}
