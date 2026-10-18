<?php

declare(strict_types=1);

namespace DecisionsLint\Lint;

use DecisionsLint\Rule\Rule;
use DecisionsLint\Source\Target;

/**
 * One rule applied to one target, answered within one inquiry of the plan.
 *
 * @property-read int $inquiry the index of the inquiry in the plan
 * @property-read Rule $rule the rule asked
 * @property-read Target $target the declaration judged
 */
final class Assignment
{
    /**
     * Creates the immutable value.
     */
    public function __construct(
        /** @readonly */
        private int $inquiry,
        /** @readonly */
        private Rule $rule,
        /** @readonly */
        private Target $target,
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
            'inquiry' => $this->inquiry,
            'rule' => $this->rule,
            'target' => $this->target,
            default => null,
        };
    }
}
