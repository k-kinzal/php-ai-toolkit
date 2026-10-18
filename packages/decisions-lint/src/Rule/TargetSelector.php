<?php

declare(strict_types=1);

namespace DecisionsLint\Rule;

use DecisionsLint\Source\GlobMatcher;
use DecisionsLint\Source\Target;

/**
 * Which declarations of a file a rule judges.
 *
 * @property-read string $kind file, class, interface, trait, enum, method, function or property
 * @property-read list<string> $visibility the visibilities to keep; empty keeps all
 * @property-read list<string> $names name globs to keep; empty keeps all
 * @property-read list<string> $excludeNames name globs to drop
 * @property-read ?bool $static whether to keep only static or only non-static declarations; null keeps both
 */
final class TargetSelector
{
    /**
     * Creates the immutable value.
     *
     * @param list<string> $visibility
     * @param list<string> $names
     * @param list<string> $excludeNames
     */
    public function __construct(
        /** @readonly */
        private string $kind,
        /** @readonly */
        private array $visibility = [],
        /** @readonly */
        private array $names = [],
        /** @readonly */
        private array $excludeNames = [],
        /** @readonly */
        private ?bool $static = null,
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
            'kind' => $this->kind,
            'visibility' => $this->visibility,
            'names' => $this->names,
            'excludeNames' => $this->excludeNames,
            'static' => $this->static,
            default => null,
        };
    }

    /**
     * Tells whether a target is one the rule judges.
     */
    public function selects(Target $target): bool
    {
        if ($target->kind !== $this->kind) {
            return false;
        }
        if ($this->visibility !== [] && !in_array($target->visibility, $this->visibility, true)) {
            return false;
        }
        if ($this->static !== null && in_array('static', $target->modifiers, true) !== $this->static) {
            return false;
        }
        $matcher = new GlobMatcher();
        if ($this->names !== [] && !$matcher->matchesAny($this->names, $target->name)) {
            return false;
        }

        return $this->excludeNames === [] || !$matcher->matchesAny($this->excludeNames, $target->name);
    }
}
