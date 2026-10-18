<?php

declare(strict_types=1);

namespace DecisionsLint\Rule;

/**
 * One nuanced rule: which declarations it judges, what it asks the model, and which answers violate it.
 *
 * @property-read string $id the identifier findings and overrides use
 * @property-read string $description what the rule protects, for humans
 * @property-read string $severity error, warning or info
 * @property-read bool $enabled whether the rule runs
 * @property-read list<string> $paths file globs the rule applies to; empty means the configured paths
 * @property-read list<string> $exclude file globs the rule skips
 * @property-read TargetSelector $target which declarations are judged
 * @property-read string $context declaration, class or file
 * @property-read DecisionSpec $decision the question and threshold
 * @property-read list<Violation> $violations the answers that violate the rule
 */
final class Rule
{
    /**
     * Creates the immutable value.
     *
     * @param list<string> $paths
     * @param list<string> $exclude
     * @param list<Violation> $violations
     */
    public function __construct(
        /** @readonly */
        private string $id,
        /** @readonly */
        private string $description,
        /** @readonly */
        private string $severity,
        /** @readonly */
        private bool $enabled,
        /** @readonly */
        private array $paths,
        /** @readonly */
        private array $exclude,
        /** @readonly */
        private TargetSelector $target,
        /** @readonly */
        private string $context,
        /** @readonly */
        private DecisionSpec $decision,
        /** @readonly */
        private array $violations,
    ) {
    }

    /**
     * Returns a declared immutable property.
     *
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'id' => $this->id,
            'description' => $this->description,
            'severity' => $this->severity,
            'enabled' => $this->enabled,
            'paths' => $this->paths,
            'exclude' => $this->exclude,
            'target' => $this->target,
            'context' => $this->context,
            'decision' => $this->decision,
            'violations' => $this->violations,
            default => null,
        };
    }
}
