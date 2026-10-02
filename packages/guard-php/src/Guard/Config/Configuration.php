<?php

declare(strict_types=1);

namespace Toolkit\Guard\Config;

/**
 * Validated project policy shared by check and apply.
 *
 * @property-read string $root
 * @property-read ?\Toolkit\LocGuard\Config\LocGuardConfig $metrics
 * @property-read ?\Toolkit\TreeGuard\Config\TreeGuardConfig $structure
 * @property-read ?\Toolkit\DocGuard\Config\DocGuardConfig $documentation
 * @property-read list<\Toolkit\Guard\Policy\Rule> $rules
 */
final class Configuration
{
    /**
     * Creates the immutable value.
     * @param string $root
     * @param ?\Toolkit\LocGuard\Config\LocGuardConfig $metrics
     * @param ?\Toolkit\TreeGuard\Config\TreeGuardConfig $structure
     * @param ?\Toolkit\DocGuard\Config\DocGuardConfig $documentation
     * @param list<\Toolkit\Guard\Policy\Rule> $rules
     */
    public function __construct(
        /** @readonly */
        private string $root,
        /** @readonly */
        private ?\Toolkit\LocGuard\Config\LocGuardConfig $metrics,
        /** @readonly */
        private ?\Toolkit\TreeGuard\Config\TreeGuardConfig $structure,
        /** @readonly */
        private ?\Toolkit\DocGuard\Config\DocGuardConfig $documentation,
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
