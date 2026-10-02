<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Package;

use function array_key_exists;
use function glob;

use const GLOB_ONLYDIR;

use function is_dir;
use function is_file;
use function realpath;
use function rtrim;

use Toolkit\DocGen\Config\DocGenConfig;
use Toolkit\DocGen\DocGenException;

use function usort;

/**
 * Discovers the composer packages selected by the DocGen configuration.
 *
 * Project packages come from directory globs relative to the project root;
 * a repository without a root composer.json is supported because glob entries
 * without a manifest are skipped. Vendor packages are added afterwards: the
 * "vendor" globs select installed runtime dependencies and the "vendor_dev"
 * globs select installed dev dependencies.
 */
final class PackageDiscovery
{
    /** @readonly */
    private ComposerManifestReader $manifestReader;

    /** @readonly */
    private VendorPackageLocator $vendorLocator;

    /** @readonly */
    private DevPackageResolver $devResolver;

    /**
     * Creates a package discovery from manifest reading collaborators.
     */
    public function __construct(
        ?ComposerManifestReader $manifestReader = null,
        ?VendorPackageLocator $vendorLocator = null,
        ?DevPackageResolver $devResolver = null,
    ) {
        $this->manifestReader = $manifestReader ?? new ComposerManifestReader();
        $this->vendorLocator = $vendorLocator ?? new VendorPackageLocator();
        $this->devResolver = $devResolver ?? new DevPackageResolver();
    }

    /**
     * Discovers all documented packages for the given configuration.
     *
     * @return list<DiscoveredPackage>
     *
     * @throws DocGenException when no package can be discovered
     */
    public function discover(DocGenConfig $config): array
    {
        $seenDirectories = [];
        $seenNames = [];
        $packages = [];
        foreach ($config->packages as $pattern) {
            foreach ($this->candidateDirectories($config->root, $pattern) as $directory) {
                $canonical = realpath($directory);
                if ($canonical === false || array_key_exists($canonical, $seenDirectories)) {
                    continue;
                }

                $seenDirectories[$canonical] = true;
                if (!is_file($canonical . '/composer.json')) {
                    continue;
                }

                $manifest = $this->manifestReader->read($canonical . '/composer.json');
                if (array_key_exists($manifest->name, $seenNames)) {
                    continue;
                }

                $seenNames[$manifest->name] = true;
                $packages[] = new DiscoveredPackage($manifest, false);
            }
        }

        if ($packages === []) {
            throw new DocGenException(
                'No composer packages found. Pass --packages=GLOBS with directory globs where at least one directory contains a composer.json.',
            );
        }

        usort($packages, static fn (DiscoveredPackage $a, DiscoveredPackage $b): int => $a->manifest->name <=> $b->manifest->name);

        return $this->withVendorPackages($config, $packages);
    }

    /**
     * Appends the configured runtime and dev vendor packages to the set.
     *
     * @param list<DiscoveredPackage> $packages
     *
     * @return list<DiscoveredPackage>
     */
    public function withVendorPackages(DocGenConfig $config, array $packages): array
    {
        if ($config->vendor === [] && $config->vendorDev === []) {
            return $packages;
        }

        $searchDirectories = $this->searchDirectories($config, $packages);
        $devNames = $this->devResolver->devNames($searchDirectories, $packages);
        $packages = $this->appendVendorPackages(
            $packages,
            $this->vendorLocator->locate($searchDirectories, $config->vendor),
            $devNames,
            false,
        );

        return $this->appendVendorPackages(
            $packages,
            $this->vendorLocator->locate($searchDirectories, $config->vendorDev),
            $devNames,
            true,
        );
    }

    /**
     * Appends the vendor manifests that carry the requested dependency kind.
     *
     * @param list<DiscoveredPackage> $packages
     * @param list<string> $manifestPaths
     * @param array<string, true> $devNames names of the installed dev dependencies
     * @param bool $dev true to keep dev dependencies, false to keep runtime ones
     *
     * @return list<DiscoveredPackage>
     */
    public function appendVendorPackages(array $packages, array $manifestPaths, array $devNames, bool $dev): array
    {
        $seenNames = [];
        foreach ($packages as $package) {
            $seenNames[$package->manifest->name] = true;
        }

        foreach ($manifestPaths as $manifestPath) {
            $manifest = $this->manifestReader->read($manifestPath);
            if (array_key_exists($manifest->name, $seenNames) || array_key_exists($manifest->name, $devNames) !== $dev) {
                continue;
            }

            $seenNames[$manifest->name] = true;
            $packages[] = new DiscoveredPackage($manifest, true, $dev);
        }

        return $packages;
    }

    /**
     * Lists the directories whose vendor and lock files are inspected.
     *
     * @param list<DiscoveredPackage> $packages
     *
     * @return list<string>
     */
    public function searchDirectories(DocGenConfig $config, array $packages): array
    {
        $directories = [$config->root];
        foreach ($packages as $package) {
            $directories[] = $package->manifest->directory;
        }

        return $directories;
    }

    /**
     * Expands one packages glob into candidate package directories.
     *
     * @return list<string>
     */
    public function candidateDirectories(string $root, string $pattern): array
    {
        if ($pattern === '.') {
            return [$root];
        }

        $absolute = rtrim($root, '/') . '/' . $pattern;
        $directories = [];
        $matches = glob($absolute, GLOB_ONLYDIR);
        foreach ($matches === false ? [] : $matches as $directory) {
            if (is_dir($directory)) {
                $directories[] = $directory;
            }
        }

        return $directories;
    }
}
