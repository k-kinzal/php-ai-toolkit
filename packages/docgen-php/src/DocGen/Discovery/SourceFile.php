<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Discovery;

/**
 * One selected PHP file, with its parsing identity already resolved.
 *
 * @property-read string $path
 * @property-read string $relative
 * @property-read string $packageName
 * @property-read bool $isDev
 */
final class SourceFile
{
    /**
     * Creates one selected PHP file, with its parsing identity already resolved.

     */
    public function __construct(
        /** @readonly */
        private string $path,
        /** @readonly */
        private string $relative,
        /** @readonly */
        private string $packageName,
        /** @readonly */
        private bool $isDev,
    ) {
    }

    /**
     * Provides read-only access to the stage's values.
     *
     * @return mixed the requested value
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'path' => $this->path,
            'relative' => $this->relative,
            'packageName' => $this->packageName,
            'isDev' => $this->isDev,
            default => null,
        };
    }
}
