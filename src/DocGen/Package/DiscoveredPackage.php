<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Package;

use function get_object_vars;

/**
 * One package selected for documentation.
 *
 * A vendor package is additionally flagged as a dev dependency when it is
 * installed only for development, so renderers can tell both apart.
 *
 * @property-read ComposerManifest $manifest
 * @property-read bool $isVendor
 * @property-read bool $isDevDependency
 */
final class DiscoveredPackage
{
    /**
     * Creates one documented package selection.
     */
    public function __construct(
        /** @readonly */
        private ComposerManifest $manifest,
        /** @readonly */
        private bool $isVendor,
        /** @readonly */
        private bool $isDevDependency = false,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return get_object_vars($this)[$name] ?? null;
    }
}
