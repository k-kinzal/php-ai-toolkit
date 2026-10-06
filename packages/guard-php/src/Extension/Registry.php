<?php

declare(strict_types=1);

namespace Guard\Extension;

use Guard\Collect\Collector;
use Guard\Collect\Subject;
use Guard\Policy\Policy;
use Guard\Policy\PolicyException;

/**
 * Explicit registration boundary for built-in and external guard extensions.
 */
final class Registry
{
    /** @var array<string, Collector> */
    private array $collectors = [];
    /** @var array<string, PolicyBinding> */
    private array $policies = [];
    /**
     * Adds an input collector once; duplicate ids are errors, not implicit replacements.
     * @throws PolicyException
     */
    public function addCollector(string $id, Collector $collector): void
    {
        if ($id === '' || isset($this->collectors[$id])) {
            throw new PolicyException('Collector id "' . $id . '" is empty or already registered. Choose a unique non-empty id.');
        }
        $this->collectors[$id] = $collector;
    }
    /**
     * Binds a policy to an information class or interface in evaluation order.
     * @param class-string $informationType
     * @throws PolicyException
     */
    public function addPolicy(string $id, string $informationType, Policy $policy): void
    {
        if ($id === '' || isset($this->policies[$id])) {
            throw new PolicyException('Policy id "' . $id . '" is empty or already registered. Choose a unique non-empty id.');
        }
        if (!is_a($informationType, Subject::class, true)) {
            throw new PolicyException('Information type "' . $informationType . '" must implement ' . Subject::class . '. Register a structured information type.');
        }
        $this->policies[$id] = new PolicyBinding($id, $informationType, $policy);
    }
    /**
     * @return list<Collector>
     */
    public function collectors(): array
    {
        return array_values($this->collectors);
    }
    /**
     * @return list<PolicyBinding>
     */
    public function policies(): array
    {
        return array_values($this->policies);
    }
}
