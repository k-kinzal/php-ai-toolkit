<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render\Page\Component;

use Toolkit\DocGen\Analysis\Diff\DiffStatus;

/**
 * One symbol entry of a navigation or index listing.
 *
 * @property-read string $kind
 * @property-read string $name
 * @property-read string $fqcn
 * @property-read string $page
 * @property-read string $summary
 * @property-read list<string> $layers
 * @property-read string $namespace
 * @property-read string $status
 */
final class SymbolRow
{
    /**
     * @param list<string> $layers
     * @param string $status the diff state of the listed symbol
     */
    public function __construct(
        /** @readonly */
        private string $kind,
        /** @readonly */
        private string $name,
        /** @readonly */
        private string $fqcn,
        /** @readonly */
        private string $page,
        /** @readonly */
        private string $summary,
        /** @readonly */
        private array $layers,
        /** @readonly */
        private string $namespace = '',
        /** @readonly */
        private string $status = DiffStatus::SAME,
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
            'kind' => $this->kind,
            'name' => $this->name,
            'fqcn' => $this->fqcn,
            'page' => $this->page,
            'summary' => $this->summary,
            'layers' => $this->layers,
            'namespace' => $this->namespace,
            'status' => $this->status,
            default => null,
        };
    }
}
