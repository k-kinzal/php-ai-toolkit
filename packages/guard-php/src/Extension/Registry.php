<?php

declare(strict_types=1);

namespace Guard\Extension;

use Guard\Policy\Policy;
use Guard\Policy\PolicyException;
use Guard\Structure\Structurer;

/**
 * Registers structures and policies; file collection is shared by all registrations.
 */
final class Registry
{
    /** @var array<string, Structurer> */
    private array $structurers = [];
    /** @var array<string, PolicyBinding> */
    private array $policies = [];
    /** Registers a reusable structure producer.
     * @throws PolicyException
     */
    public function addStructure(string $id, Structurer $structurer): void
    {
        if ($id === '' || isset($this->structurers[$id])) {
            throw new PolicyException('Structure id "' . $id . '" is empty or already registered. Choose a unique non-empty id.');
        }
        $this->structurers[$id] = $structurer;
    }
    /** Registers a policy that declares its own input requirements.
     * @throws PolicyException
     */
    public function addPolicy(string $id, Policy $policy, int $reportOrder = 0): void
    {
        if ($id === '' || isset($this->policies[$id])) {
            throw new PolicyException('Policy id "' . $id . '" is empty or already registered. Choose a unique non-empty id.');
        }
        $this->policies[$id] = new PolicyBinding($id, $policy, $reportOrder);
    }
    /**
     * @return array<string, Structurer>
     */
    public function structures(): array
    {
        return $this->structurers;
    }
    /**
     * @return list<PolicyBinding>
     */
    public function policies(): array
    {
        return array_values($this->policies);
    }
}
