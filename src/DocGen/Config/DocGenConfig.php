<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Config;

/**
 * Immutable DocGen configuration loaded from doc.yaml.
 *
 * The configuration deliberately covers only the documented code scope and
 * the output location; page content and design are fixed by the generator.
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
 */
final class DocGenConfig
{
    /**
     * Where the generation caches are kept unless a project says otherwise.
     */
    public const DEFAULT_CACHE = 'build/doc-gen-cache';

    /**
     * @param list<string> $packages
     * @param list<string> $vendor package name globs for installed runtime dependencies
     * @param list<string> $exclude
     * @param list<string> $vendorDev package name globs for installed dev dependencies
     * @param ?string $cache the cache directory, or null to cache nothing
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
            default => null,
        };
    }
}
