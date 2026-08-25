<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Package;

use function array_key_exists;
use function array_keys;

/**
 * Builds the dependency graph between the documented packages.
 *
 * Only edges whose both endpoints are documented packages are kept; external
 * dependencies stay visible on each package page instead.
 */
final class PackageGraphBuilder
{
    /**
     * Builds dependency edges between the given packages.
     *
     * @param list<DiscoveredPackage> $packages
     */
    public function build(array $packages): PackageGraph
    {
        $known = [];
        foreach ($packages as $package) {
            $known[$package->manifest->name] = true;
        }

        $edges = [];
        foreach ($packages as $package) {
            $sections = [
                'require' => array_keys($package->manifest->requires),
                'require-dev' => array_keys($package->manifest->devRequires),
                'suggest' => array_keys($package->manifest->suggests),
            ];
            foreach ($sections as $kind => $names) {
                foreach ($names as $name) {
                    if ($name !== $package->manifest->name && array_key_exists($name, $known)) {
                        $edges[] = new PackageDependency($package->manifest->name, $name, $kind);
                    }
                }
            }
        }

        return new PackageGraph($edges);
    }
}
