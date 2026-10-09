<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis;

/**
 * The metadata and auxiliary reports used to assemble a documented project.
 *
 * @property-read string $title
 * @property-read ?string $deptrac
 * @property-read ?string $coverage
 * @property-read ?string $baseUrl
 * @property-read ?string $repository
 * @property-read bool $publicApi
 */
final class AnalysisOptions
{
    /**
     * Creates the metadata and auxiliary reports used to assemble a documented project.

     */
    public function __construct(
        /** @readonly */
        private string $title,
        /** @readonly */
        private ?string $deptrac,
        /** @readonly */
        private ?string $coverage,
        /** @readonly */
        private ?string $baseUrl = null,
        /** @readonly */
        private ?string $repository = null,
        /** @readonly */
        private bool $publicApi = false,
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
            'title' => $this->title,
            'deptrac' => $this->deptrac,
            'coverage' => $this->coverage,
            'baseUrl' => $this->baseUrl,
            'repository' => $this->repository,
            'publicApi' => $this->publicApi,
            default => null,
        };
    }
}
