<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Filesystem;

use function ksort;

use PhpAiToolkit\Doctest\Config\DoctestConfig;
use PhpAiToolkit\Doctest\DoctestException;

/**
 * Finds the PHP files a doctest run scans for examples.
 */
final class PhpFileFinder
{
    /** @readonly */
    private DoctestPathResolver $pathResolver;

    /** @readonly */
    private PhpPathFileCollector $pathFileCollector;

    /**
     * Creates a finder from path resolution and per-path collection.
     */
    public function __construct(
        ?DoctestPathResolver $pathResolver = null,
        ?PhpPathFileCollector $pathFileCollector = null,
    ) {
        $this->pathResolver = $pathResolver ?? new DoctestPathResolver();
        $this->pathFileCollector = $pathFileCollector ?? new PhpPathFileCollector();
    }

    /**
     * Returns every scannable PHP file as a map of absolute to relative path.
     *
     * The map is sorted, so the same sources produce the same run order on
     * every platform and PHP version.
     *
     * @return array<string, string>
     *
     * @throws DoctestException when a configured path does not exist
     */
    public function find(DoctestConfig $config): array
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
