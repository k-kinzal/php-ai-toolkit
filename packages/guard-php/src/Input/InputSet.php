<?php

declare(strict_types=1);

namespace Guard\Input;

use Guard\Diagnostic\PolicyException;
use JsonException;
use RuntimeException;

/**
 * Provides the named inputs declared by one policy.
 */
final class InputSet
{
    /**
     * @param array<string, FileSet> $sets
     */
    public function __construct(private array $sets)
    {
    }
    /** Raises a selection error before evaluating any file in this policy.
     * @throws RuntimeException
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function validate(): void
    {
        foreach ($this->sets as $set) {
            $set->validate();
        }
    }
    /** Returns one declared selection.
     * @throws PolicyException
     */
    public function get(string $name): FileSet
    {
        if (!isset($this->sets[$name])) {
            throw new PolicyException('Input "' . $name . '" was not declared. Return it from the policy inputs() method.');
        }
        return $this->sets[$name];
    }
}
