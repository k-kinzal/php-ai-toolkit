<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Filesystem;

use function count;
use function is_dir;
use function ksort;

use PhpAiToolkit\TreeGuard\Config\TreeGuardConfig;
use PhpAiToolkit\TreeGuard\TreeGuardException;

use function sprintf;

/**
 * Scans configured paths into per-directory listings in one filesystem pass.
 *
 * Excluded files are dropped from their listing and excluded directories are
 * pruned together with their whole subtree.
 */
final class DirectoryTreeScanner
{
    /** @readonly */
    private TreeGuardPathResolver $pathResolver;

    /** @readonly */
    private PathInclusionPolicy $inclusionPolicy;

    /** @readonly */
    private DirectoryListingReader $listingReader;

    /**
     * Creates a scanner from path resolution, exclusion, and per-directory reading.
     */
    public function __construct(
        ?TreeGuardPathResolver $pathResolver = null,
        ?PathInclusionPolicy $inclusionPolicy = null,
        ?DirectoryListingReader $listingReader = null,
    ) {
        $this->pathResolver = $pathResolver ?? new TreeGuardPathResolver();
        $this->inclusionPolicy = $inclusionPolicy ?? new PathInclusionPolicy();
        $this->listingReader = $listingReader ?? new DirectoryListingReader();
    }

    /**
     * Returns listings for every scanned directory, keyed and sorted by relative path.
     *
     * @return array<string, DirectoryListing>
     */
    public function scan(TreeGuardConfig $config): array
    {
        $listings = [];
        foreach ($config->paths as $path) {
            $absolutePath = $this->pathResolver->absolute($config->root, $path);
            if (!is_dir($absolutePath)) {
                throw new TreeGuardException(sprintf('Configured path is not a directory: %s', $path));
            }

            $queue = [[$absolutePath, $this->pathResolver->relative($config->root, $absolutePath)]];
            for ($index = 0; $index < count($queue); $index++) {
                [$absoluteDir, $relativeDir] = $queue[$index];
                $entries = $this->listingReader->read($absoluteDir);
                $fileNames = [];
                foreach ($entries['files'] as $name) {
                    if ($this->inclusionPolicy->includes($config, $relativeDir . '/' . $name)) {
                        $fileNames[] = $name;
                    }
                }
                $dirNames = [];
                foreach ($entries['dirs'] as $name) {
                    if ($this->inclusionPolicy->includes($config, $relativeDir . '/' . $name)) {
                        $dirNames[] = $name;
                        $queue[] = [$absoluteDir . '/' . $name, $relativeDir . '/' . $name];
                    }
                }
                $listings[$relativeDir] = new DirectoryListing($relativeDir, $fileNames, $dirNames);
            }
        }

        ksort($listings);

        return $listings;
    }
}
