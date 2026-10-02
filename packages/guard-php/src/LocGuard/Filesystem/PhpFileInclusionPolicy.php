<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Filesystem;

use function array_pop;
use function explode;
use function implode;
use function str_ends_with;

use Toolkit\LocGuard\Config\LocGuardConfig;

/**
 * Decides whether a discovered file belongs in LocGuard analysis.
 */
final class PhpFileInclusionPolicy
{
    /** @readonly */
    private LocGuardPathResolver $pathResolver;

    /** @readonly */
    private FilePathPatternMatcher $patternMatcher;

    /**
     * Creates an inclusion policy from path resolution.
     */
    public function __construct(
        ?LocGuardPathResolver $pathResolver = null,
        ?FilePathPatternMatcher $patternMatcher = null,
    ) {
        $this->pathResolver = $pathResolver ?? new LocGuardPathResolver();
        $this->patternMatcher = $patternMatcher ?? new FilePathPatternMatcher();
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

        $candidate = explode('/', $relativePath);
        foreach ($config->scan->exclude as $pattern) {
            while ($candidate !== []) {
                if ($this->patternMatcher->matches($pattern, implode('/', $candidate))) {
                    return false;
                }
                array_pop($candidate);
            }
            $candidate = explode('/', $relativePath);
        }

        return true;
    }
}
