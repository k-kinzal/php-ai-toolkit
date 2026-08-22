<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Config;

/**
 * Immutable DocGen configuration, as one command line described it.
 *
 * The configuration deliberately covers only the documented code scope and
 * the output location; page content and design are fixed by the generator.
 * Everything here is named by an option of the run, so what a site was
 * generated from is the command that generated it, and a continuous
 * integration job can pass the values only it knows, such as the address the
 * site is published at.
 *
 * @property-read string $root
 * @property-read list<string> $packages
 * @property-read list<string> $vendor
 * @property-read list<string> $exclude
 * @property-read string $output
 * @property-read ?string $title
 * @property-read ?string $deptrac
 * @property-read ?string $coverage
 * @property-read list<string> $vendorDev
 * @property-read ?string $cache
 * @property-read ?string $baseUrl
 * @property-read ?string $repository
 */
final class DocGenConfig
{
    /**
     * Where the generation caches are kept unless a run says otherwise.
     */
    public const DEFAULT_CACHE = 'build/doc-gen-cache';

    /**
     * The directory globs probed for a composer.json unless a run names its own.
     *
     * @var list<string>
     */
    public const DEFAULT_PACKAGES = ['.', 'packages/*'];

    /**
     * Where the generated site is written unless a run names its own directory.
     */
    public const DEFAULT_OUTPUT = 'build/docs';

    /**
     * @param list<string> $packages
     * @param list<string> $vendor package name globs for installed runtime dependencies
     * @param list<string> $exclude
     * @param list<string> $vendorDev package name globs for installed dev dependencies
     * @param ?string $cache the cache directory, or null to cache nothing
     * @param ?string $baseUrl the address the site is published at, without a trailing slash, or null when it is unknown
     * @param ?string $repository the address of the repository the project lives in, or null to read it from the root package
     */
    public function __construct(
        /** @readonly */
        private string $root,
        /** @readonly */
        private array $packages,
        /** @readonly */
        private array $vendor,
        /** @readonly */
        private array $exclude,
        /** @readonly */
        private string $output,
        /** @readonly */
        private ?string $title,
        /** @readonly */
        private ?string $deptrac,
        /** @readonly */
        private ?string $coverage,
        /** @readonly */
        private array $vendorDev = [],
        /** @readonly */
        private ?string $cache = self::DEFAULT_CACHE,
        /** @readonly */
        private ?string $baseUrl = null,
        /** @readonly */
        private ?string $repository = null,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'root' => $this->root,
            'packages' => $this->packages,
            'vendor' => $this->vendor,
            'exclude' => $this->exclude,
            'output' => $this->output,
            'title' => $this->title,
            'deptrac' => $this->deptrac,
            'coverage' => $this->coverage,
            'vendorDev' => $this->vendorDev,
            'cache' => $this->cache,
            'baseUrl' => $this->baseUrl,
            'repository' => $this->repository,
            default => null,
        };
    }
}
