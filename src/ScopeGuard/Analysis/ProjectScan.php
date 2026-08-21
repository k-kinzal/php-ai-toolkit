<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Analysis;

use PhpAiToolkit\ScopeGuard\Analysis\Declaration\DeclarationIndex;
use PhpAiToolkit\ScopeGuard\Analysis\Reference\Reference;

/**
 * Everything one pass over the analyzed sources found.
 *
 * @property-read DeclarationIndex $index
 * @property-read list<Reference> $references
 * @property-read int $fileCount
 */
final class ProjectScan
{
    /**
     * @param list<Reference> $references
     */
    public function __construct(
        /** @readonly */
        private DeclarationIndex $index,
        /** @readonly */
        private array $references,
        /** @readonly */
        private int $fileCount,
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
            'index' => $this->index,
            'references' => $this->references,
            'fileCount' => $this->fileCount,
            default => null,
        };
    }
}
