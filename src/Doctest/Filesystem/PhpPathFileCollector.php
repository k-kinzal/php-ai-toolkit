<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Filesystem;

use function is_dir;
use function is_file;

use PhpAiToolkit\Doctest\Config\DoctestConfig;
use PhpAiToolkit\Doctest\DoctestException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function sprintf;

/**
 * Collects PHP files from one configured absolute path.
 *
 * @visibility parent
 */
final class PhpPathFileCollector
{
    /** @readonly */
    private PhpFileInclusionPolicy $inclusionPolicy;

    /** @readonly */
    private DoctestPathResolver $pathResolver;

    /**
     * Creates a collector from inclusion and path resolution policies.
     */
    public function __construct(
        ?PhpFileInclusionPolicy $inclusionPolicy = null,
        ?DoctestPathResolver $pathResolver = null,
    ) {
        $this->inclusionPolicy = $inclusionPolicy ?? new PhpFileInclusionPolicy();
        $this->pathResolver = $pathResolver ?? new DoctestPathResolver();
    }

    /**
     * Returns PHP files under the configured path.
     *
     * @return array<string, string>
     *
     * @throws DoctestException when the configured path does not exist
     */
    public function files(DoctestConfig $config, string $path): array
    {
        if (is_file($path)) {
            return $this->inclusionPolicy->includes($config, $path) ? [$path => $this->pathResolver->relative($config->root, $path)] : [];
        }

        if (!is_dir($path)) {
            throw new DoctestException(sprintf('Configured path does not exist: %s', $path));
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            $file = $item->getPathname();
            if ($this->inclusionPolicy->includes($config, $file)) {
                $files[$file] = $this->pathResolver->relative($config->root, $file);
            }
        }

        return $files;
    }
}
