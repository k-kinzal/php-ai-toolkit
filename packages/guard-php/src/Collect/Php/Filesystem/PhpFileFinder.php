<?php

declare(strict_types=1);

namespace Guard\Collect\Php\Filesystem;

use Guard\Config\Loc\MetricsConfig;

use function ksort;

/**
 * Finds PHP files from configured source paths.
 */
final class PhpFileFinder
{
    /** @readonly */
    private PathResolver $pathResolver;

    /** @readonly */
    private PhpPathFileCollector $pathFileCollector;

    /**
     * Creates a finder from path resolution and per-path collection.
     */
    public function __construct(
        ?PathResolver $pathResolver = null,
        ?PhpPathFileCollector $pathFileCollector = null,
    ) {
        $this->pathResolver = $pathResolver ?? new PathResolver();
        $this->pathFileCollector = $pathFileCollector ?? new PhpPathFileCollector();
    }

    /**
     * @return array<string, string> map of absolute path to relative path
     */
    public function find(MetricsConfig $config): array
    {
        $files = [];
        foreach ($config->scan->roots as $path) {
            $absolutePath = $this->pathResolver->absolute($config->root, $path);
            $files += $this->pathFileCollector->files($config, $absolutePath);
        }

        ksort($files);

        return $files;
    }
}
