<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Discovery;

use Toolkit\DocGen\Discovery\Filesystem\DocGenPathResolver;
use Toolkit\DocGen\Discovery\Internal\DocumentCollector;
use Toolkit\DocGen\Discovery\Internal\Filesystem\SourceFileFinder;
use Toolkit\DocGen\Discovery\Internal\Package\PackageDiscovery;
use Toolkit\DocGen\Discovery\Package\DiscoveredPackage;
use Toolkit\DocGen\DocGenException;

/**
 * Resolves package selections into an ordered set of concrete source files.
 */
final class SourceDiscovery
{
    /** @readonly */
    private PackageDiscovery $discovery;
    /** @readonly */
    private SourceFileFinder $fileFinder;
    /** @readonly */
    private DocGenPathResolver $pathResolver;
    /** @readonly */
    private DocumentCollector $documents;
    /**
     * Creates the discovery stage and its filesystem readers.
     */
    public function __construct(
        ?PackageDiscovery $discovery = null,
        ?SourceFileFinder $fileFinder = null,
        ?DocGenPathResolver $pathResolver = null,
        ?DocumentCollector $documents = null,
    ) {
        $this->discovery = $discovery ?? new PackageDiscovery();
        $this->fileFinder = $fileFinder ?? new SourceFileFinder();
        $this->pathResolver = $pathResolver ?? new DocGenPathResolver();
        $this->documents = $documents ?? new DocumentCollector();
    }

    /**
     * Finishes selection before any source is parsed.
     *
     * @throws DocGenException when no requested package exists
     */
    public function discover(SourceSelection $selection): SourceSet
    {
        $root = realpath($selection->root);
        $selection = new SourceSelection(
            $root === false ? $selection->root : $root,
            $selection->packages,
            $selection->vendor,
            $selection->exclude,
            $selection->vendorDev,
        );
        $packages = $this->discovery->discover($selection);
        $files = [];
        foreach ($this->sourceFiles($selection, $packages) as $file) {
            $files[] = new SourceFile(
                $file['file'],
                $this->pathResolver->relative($selection->root, $file['file']),
                $file['package']->manifest->name,
                $file['source']['isDev'],
            );
        }

        return new SourceSet(
            $selection->root,
            $packages,
            $files,
            $this->documents->collect($selection, $packages),
            $this->vendorWarnings($selection, $packages),
        );
    }

    /**
     * Lists every source file to parse, in discovery order and once each.
     *
     * @param list<DiscoveredPackage> $packages
     *
     * @return list<array{package: DiscoveredPackage, source: array{directory: string, isDev: bool}, file: string}>
     */
    public function sourceFiles(SourceSelection $config, array $packages): array
    {
        $found = [];
        $seenFiles = [];
        foreach ($packages as $package) {
            foreach ($this->sourceDirectories($package) as $source) {
                foreach ($this->fileFinder->find($source['directory'], $config->root, $config->exclude) as $file) {
                    if (isset($seenFiles[$file])) {
                        continue;
                    }

                    $seenFiles[$file] = true;
                    $found[] = ['package' => $package, 'source' => $source, 'file' => $file];
                }
            }
        }

        return $found;
    }

    /**
     * Lists the autoload source directories of one package.
     *
     * PSR-4 prefixes always map to directories and are taken as declared; an
     * empty prefix path is the package root. Classmap entries may name a
     * directory or a single file, so only the entries that exist as a
     * directory are kept; single classmap files are not documented.
     *
     * @return list<array{directory: string, isDev: bool}>
     */
    public function sourceDirectories(DiscoveredPackage $package): array
    {
        $sources = [];
        foreach ([['map' => $package->manifest->autoload, 'isDev' => false], ['map' => $package->manifest->devAutoload, 'isDev' => true]] as $section) {
            foreach ($section['map'] as $directories) {
                foreach ($directories as $directory) {
                    $sources[] = [
                        'directory' => $directory === '' ? $package->manifest->directory : $this->pathResolver->resolve($package->manifest->directory, $directory),
                        'isDev' => $section['isDev'],
                    ];
                }
            }
        }

        foreach ([['paths' => $package->manifest->classmap, 'isDev' => false], ['paths' => $package->manifest->devClassmap, 'isDev' => true]] as $section) {
            foreach ($section['paths'] as $path) {
                $directory = $this->pathResolver->resolve($package->manifest->directory, $path);
                if (is_dir($directory)) {
                    $sources[] = ['directory' => $directory, 'isDev' => $section['isDev']];
                }
            }
        }

        return $sources;
    }

    /**
     * Warns about unusable vendor selections.
     *
     * Both the runtime globs of "vendor" and the dev globs of "vendor_dev" are
     * checked, and every selected package that ships no documentable source is
     * reported as well.
     *
     * @param list<DiscoveredPackage> $packages
     *
     * @return list<string>
     */
    public function vendorWarnings(SourceSelection $config, array $packages): array
    {
        return array_merge(
            $this->vendorGlobWarnings($config->vendor, $packages, false),
            $this->vendorGlobWarnings($config->vendorDev, $packages, true),
            $this->vendorSourceWarnings($packages),
        );
    }

    /**
     * Warns about vendor globs that selected no package of one kind.
     *
     * Vendor globs match composer package names, so a directory name such
     * as "vendor" silently selects nothing without this warning. A glob also
     * selects nothing when it names a dev dependency while the runtime globs
     * are checked, or the other way round.
     *
     * @param list<string> $globs
     * @param list<DiscoveredPackage> $packages
     * @param bool $dev true when the dev globs are checked, false for runtime globs
     *
     * @return list<string>
     */
    public function vendorGlobWarnings(array $globs, array $packages, bool $dev): array
    {
        $warnings = [];
        foreach ($globs as $glob) {
            $matched = false;
            foreach ($packages as $package) {
                if ($package->isVendor && $package->isDevDependency === $dev && fnmatch($glob, $package->manifest->name)) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $warnings[] = sprintf(
                    'Vendor glob "%s" documented no installed %s vendor package. Vendor globs match composer package names such as "acme/lib" or "acme/*", not directory names.',
                    $glob,
                    $dev ? 'dev' : 'runtime',
                );
            }
        }

        return $warnings;
    }

    /**
     * Warns about selected vendor packages without documentable sources.
     *
     * A package that autoloads only "files" entries, such as a phar bootstrap,
     * exposes no source directory to parse, so none of its classes can appear
     * in the site or be used as a link target.
     *
     * @param list<DiscoveredPackage> $packages
     *
     * @return list<string>
     */
    public function vendorSourceWarnings(array $packages): array
    {
        $warnings = [];
        foreach ($packages as $package) {
            if ($package->isVendor && $this->sourceDirectories($package) === []) {
                $warnings[] = sprintf(
                    'Vendor package "%s" declares no PSR-4 or classmap autoload source, so its classes cannot be documented or linked. Packages that autoload only "files" entries, such as a phar bootstrap, cannot be documented: drop "%s" from the vendor globs.',
                    $package->manifest->name,
                    $package->manifest->name,
                );
            }
        }

        return $warnings;
    }

}
