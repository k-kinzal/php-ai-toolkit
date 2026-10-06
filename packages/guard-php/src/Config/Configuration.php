<?php

declare(strict_types=1);

namespace Guard\Config;

/**
 * Validated project policy shared by check and apply.
 *
 * @property-read string $root
 * @property-read ?Loc\MetricsConfig $metrics
 * @property-read ?Tree\StructureConfig $structure
 * @property-read ?Doc\DocumentationConfig $documentation
 * @property-read list<\Guard\Policy\Rule> $rules
 */
final class Configuration
{
    /**
     * Creates the immutable value.
     * @param string $root
     * @param ?Loc\MetricsConfig $metrics
     * @param ?Tree\StructureConfig $structure
     * @param ?Doc\DocumentationConfig $documentation
     * @param list<\Guard\Policy\Rule> $rules
     */
    public function __construct(
        /** @readonly */
        private string $root,
        /** @readonly */
        private ?Loc\MetricsConfig $metrics,
        /** @readonly */
        private ?Tree\StructureConfig $structure,
        /** @readonly */
        private ?Doc\DocumentationConfig $documentation,
        /** @readonly */
        private array $rules,
    ) {
    }

    /** Returns a declared immutable property.
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'root' => $this->root,
            'metrics' => $this->metrics,
            'structure' => $this->structure,
            'documentation' => $this->documentation,
            'rules' => $this->rules,
            default => null,
        };
    }
}
