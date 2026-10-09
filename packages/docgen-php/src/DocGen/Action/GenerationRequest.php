<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Action;

/**
 * One generation request after command-line validation.
 *
 * @property-read Config\DocGenConfig $config
 * @property-read ?int $workers
 * @property-read ?string $base
 * @property-read ?string $head
 * @property-read bool $clearCache
 * @property-read string $cacheDirectory
 */
final class GenerationRequest
{
    /**
     * Creates one generation request after command-line validation.

     */
    public function __construct(
        /** @readonly */
        private Config\DocGenConfig $config,
        /** @readonly */
        private ?int $workers = null,
        /** @readonly */
        private ?string $base = null,
        /** @readonly */
        private ?string $head = null,
        /** @readonly */
        private bool $clearCache = false,
        /** @readonly */
        private string $cacheDirectory = 'build/docgen-cache',
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
            'config' => $this->config,
            'workers' => $this->workers,
            'base' => $this->base,
            'head' => $this->head,
            'clearCache' => $this->clearCache,
            'cacheDirectory' => $this->cacheDirectory,
            default => null,
        };
    }
}
