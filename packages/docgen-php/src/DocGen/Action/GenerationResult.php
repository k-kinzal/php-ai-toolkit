<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Action;

/**
 * The completed generation summary returned to the CLI.
 *
 * @property-read string $outputRoot
 * @property-read int $pages
 * @property-read int $packages
 * @property-read list<string> $warnings
 * @property-read ?string $cacheSummary
 * @property-read ?string $baseLabel
 * @property-read ?string $headLabel
 */
final class GenerationResult
{
    /**
     * Creates the completed generation summary returned to the CLI.
     * @param list<string> $warnings
     */
    public function __construct(
        /** @readonly */
        private string $outputRoot,
        /** @readonly */
        private int $pages,
        /** @readonly */
        private int $packages,
        /** @readonly */
        private array $warnings,
        /** @readonly */
        private ?string $cacheSummary = null,
        /** @readonly */
        private ?string $baseLabel = null,
        /** @readonly */
        private ?string $headLabel = null,
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
            'outputRoot' => $this->outputRoot,
            'pages' => $this->pages,
            'packages' => $this->packages,
            'warnings' => $this->warnings,
            'cacheSummary' => $this->cacheSummary,
            'baseLabel' => $this->baseLabel,
            'headLabel' => $this->headLabel,
            default => null,
        };
    }
}
