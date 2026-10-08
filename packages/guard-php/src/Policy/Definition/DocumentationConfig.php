<?php

declare(strict_types=1);

namespace Guard\Policy\Definition;

/**
 * Fully resolved documentation configuration.
 *
 * @property-read string $root
 * @property-read string $configName
 * @property-read list<DocumentConfig> $documents
 * @property-read list<string> $scan
 * @property-read list<string> $exclude
 */
final class DocumentationConfig
{
    /**
     * @param list<DocumentConfig> $documents
     * @param list<string> $scan
     * @param list<string> $exclude
     */
    public function __construct(
        /** @readonly */
        private string $root,
        /** @readonly */
        private string $configName,
        /** @readonly */
        private array $documents,
        /** @readonly */
        private array $scan,
        /** @readonly */
        private array $exclude = [],
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
            'configName' => $this->configName,
            'documents' => $this->documents,
            'scan' => $this->scan,
            'exclude' => $this->exclude,
            default => null,
        };
    }
}
