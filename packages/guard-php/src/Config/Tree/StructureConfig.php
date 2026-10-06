<?php

declare(strict_types=1);

namespace Guard\Config\Tree;

/**
 * Fully resolved directory configuration.
 *
 * @property-read string $root
 * @property-read list<string> $paths
 * @property-read list<string> $exclude
 * @property-read list<RuleConfig> $rules
 */
final class StructureConfig
{
    /**
     * @param list<string> $paths
     * @param list<string> $exclude
     * @param list<RuleConfig> $rules
     */
    public function __construct(
        /** @readonly */
        private string $root,
        /** @readonly */
        private array $paths,
        /** @readonly */
        private array $exclude,
        /** @readonly */
        private array $rules,
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
            'paths' => $this->paths,
            'exclude' => $this->exclude,
            'rules' => $this->rules,
            default => null,
        };
    }
}
