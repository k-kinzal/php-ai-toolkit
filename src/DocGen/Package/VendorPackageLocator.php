<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Package;

use function basename;
use function dirname;
use function fnmatch;
use function glob;
use function is_dir;
use function sort;
use function sprintf;

/**
 * Locates composer.json files of installed vendor packages.
 *
 * Vendor directories of the project root and of every documented package are
 * searched, so both root-level installs and per-package installs work.
 */
final class VendorPackageLocator
{
    /**
     * Locates vendor manifest paths whose package name matches a glob.
     *
     * @param list<string> $searchDirectories
     * @param list<string> $nameGlobs
     *
     * @return list<string>
     */
    public function locate(array $searchDirectories, array $nameGlobs): array
    {
        if ($nameGlobs === []) {
            return [];
        }

        $paths = [];
        foreach ($searchDirectories as $directory) {
            $vendorDirectory = $directory . '/vendor';
            if (!is_dir($vendorDirectory)) {
                continue;
            }

            $manifestPaths = glob($vendorDirectory . '/*/*/composer.json');
            foreach ($manifestPaths === false ? [] : $manifestPaths as $manifestPath) {
                $packageDirectory = dirname($manifestPath);
                $name = sprintf(
                    '%s/%s',
                    basename(dirname($packageDirectory)),
                    basename($packageDirectory),
                );
                foreach ($nameGlobs as $glob) {
                    if (fnmatch($glob, $name)) {
                        $paths[$manifestPath] = true;
                        break;
                    }
                }
            }
        }

        $result = array_keys($paths);
        sort($result);

        return $result;
    }
}
