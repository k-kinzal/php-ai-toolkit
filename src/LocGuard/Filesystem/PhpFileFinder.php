<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Filesystem;

use function ksort;

use PhpAiToolkit\LocGuard\Config\LocGuardConfig;

/**
 * Finds PHP files from configured source paths.
 */
final class PhpFileFinder
{
    /**
     * Creates a finder from path resolution and per-path collection.
     */
    public function __construct(
        private readonly LocGuardPathResolver $pathResolver = new LocGuardPathResolver(),
        private readonly PhpPathFileCollector $pathFileCollector = new PhpPathFileCollector(),
    ) {
    }

    /**
     * @return array<string, string> map of absolute path to relative path
     */
    public function find(LocGuardConfig $config): array
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
