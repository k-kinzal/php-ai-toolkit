<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Filesystem;

use function ksort;

use PhpAiToolkit\ScopeGuard\Config\ScopeGuardConfig;
use PhpAiToolkit\ScopeGuard\ScopeGuardException;

/**
 * Finds PHP files from configured source paths.
 *
 * @visibility parent
 */
final class PhpFileFinder
{
    /** @readonly */
    private ScopeGuardPathResolver $pathResolver;

    /** @readonly */
    private PhpPathFileCollector $pathFileCollector;

    /**
     * Creates a finder from path resolution and per-path collection.
     */
    public function __construct(
        ?ScopeGuardPathResolver $pathResolver = null,
        ?PhpPathFileCollector $pathFileCollector = null,
    ) {
        $this->pathResolver = $pathResolver ?? new ScopeGuardPathResolver();
        $this->pathFileCollector = $pathFileCollector ?? new PhpPathFileCollector();
    }

    /**
     * Returns every analyzable PHP file as a map of absolute to relative path.
     *
     * @return array<string, string>
     *
     * @throws ScopeGuardException when a configured path does not exist
     */
    public function find(ScopeGuardConfig $config): array
    {
        $files = [];
        foreach ($config->paths as $path) {
            $absolutePath = $this->pathResolver->absolute($config->root, $path);
            $files += $this->pathFileCollector->files($config, $absolutePath);
        }

        ksort($files);

        return $files;
    }
}
