<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Package;

use function array_keys;
use function count;

/**
 * Decides which installed vendor packages are dev-only dependencies.
 *
 * Classification prefers composer.lock, which lists the runtime dependencies
 * under "packages" and the dev-only ones under "packages-dev". Every lock file
 * found next to the project root or next to a documented package is used, and a
 * package listed as a runtime dependency by any of them stays runtime.
 *
 * Without a lock file the classification falls back to a deliberately simple
 * rule: the runtime set is the transitive closure of the "require" sections,
 * starting at the documented project packages and following the "require"
 * section of every installed package it reaches. Anything installed but not
 * reached that way is only reachable through "require-dev" and is reported as a
 * dev dependency. The fallback ignores platform packages, replacements, and
 * version constraints, so it can mark a package as dev when it is pulled in
 * exclusively by a replaced or aliased runtime dependency.
 */
final class DevPackageResolver
{
    /** @readonly */
    private ComposerLockReader $lockReader;

    /** @readonly */
    private ComposerManifestReader $manifestReader;

    /** @readonly */
    private VendorPackageLocator $vendorLocator;

    /**
     * Creates a dev dependency resolver from its reading collaborators.
     */
    public function __construct(
        ?ComposerLockReader $lockReader = null,
        ?ComposerManifestReader $manifestReader = null,
        ?VendorPackageLocator $vendorLocator = null,
    ) {
        $this->lockReader = $lockReader ?? new ComposerLockReader();
        $this->manifestReader = $manifestReader ?? new ComposerManifestReader();
        $this->vendorLocator = $vendorLocator ?? new VendorPackageLocator();
    }

    /**
     * Returns the names of the installed packages that are dev dependencies.
     *
     * @param list<string> $searchDirectories directories whose vendor and lock files are inspected
     * @param list<DiscoveredPackage> $projectPackages the documented non-vendor packages
     *
     * @return array<string, true> package names used as a lookup set
     */
    public function devNames(array $searchDirectories, array $projectPackages): array
    {
        return $this->lockDevNames($searchDirectories) ?? $this->requireClosureDevNames($searchDirectories, $projectPackages);
    }

    /**
     * Returns the dev package names of the available composer.lock files.
     *
     * @param list<string> $searchDirectories
     *
     * @return array<string, true>|null null when no lock file exists
     */
    public function lockDevNames(array $searchDirectories): ?array
    {
        $dev = [];
        $runtime = [];
        $found = false;
        foreach ($searchDirectories as $directory) {
            $lock = $this->lockReader->read($directory . '/composer.lock');
            if ($lock === null) {
                continue;
            }

            $found = true;
            foreach ($lock['dev'] as $name) {
                $dev[$name] = true;
            }

            foreach ($lock['runtime'] as $name) {
                $runtime[$name] = true;
            }
        }

        if (!$found) {
            return null;
        }

        foreach (array_keys($runtime) as $name) {
            unset($dev[$name]);
        }

        return $dev;
    }

    /**
     * Returns the installed packages outside the "require" closure.
     *
     * @param list<string> $searchDirectories
     * @param list<DiscoveredPackage> $projectPackages
     *
     * @return array<string, true>
     */
    public function requireClosureDevNames(array $searchDirectories, array $projectPackages): array
    {
        $installed = $this->installedRequires($searchDirectories);
        $queue = [];
        foreach ($projectPackages as $package) {
            foreach (array_keys($package->manifest->requires) as $name) {
                $queue[] = $name;
            }
        }

        $runtime = [];
        for ($index = 0; $index < count($queue); $index++) {
            $name = $queue[$index];
            if (isset($runtime[$name])) {
                continue;
            }

            $runtime[$name] = true;
            foreach ($installed[$name] ?? [] as $dependency) {
                $queue[] = $dependency;
            }
        }

        $dev = [];
        foreach (array_keys($installed) as $name) {
            if (!isset($runtime[$name])) {
                $dev[$name] = true;
            }
        }

        return $dev;
    }

    /**
     * Reads the "require" section of every installed vendor package.
     *
     * @param list<string> $searchDirectories
     *
     * @return array<string, list<string>> package name to required package names
     */
    public function installedRequires(array $searchDirectories): array
    {
        $requires = [];
        foreach ($this->vendorLocator->locate($searchDirectories, ['*']) as $manifestPath) {
            $manifest = $this->manifestReader->read($manifestPath);
            $requires[$manifest->name] = array_keys($manifest->requires);
        }

        return $requires;
    }
}
