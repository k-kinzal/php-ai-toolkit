<?php

declare(strict_types=1);

namespace Guard\Config\Loc\Policy;

use Guard\Policy\PolicyException;

use function is_array;
use function is_string;

/**
 * Reads and resolves all named source metric policies.
 */
final class PolicyListConfigReader
{
    /** @readonly */
    private PolicyConfigReader $policyConfigReader;

    /** @readonly */
    private PolicyResolver $policyResolver;

    /**
     * Creates a policy-list reader from definition parsing and inheritance resolution.
     */
    public function __construct(
        ?PolicyConfigReader $policyConfigReader = null,
        ?PolicyResolver $policyResolver = null,
    ) {
        $this->policyConfigReader = $policyConfigReader ?? new PolicyConfigReader();
        $this->policyResolver = $policyResolver ?? new PolicyResolver();
    }

    /**
     * Reads a non-empty mapping of named policies.
     *
     * @param mixed $value
     * @return array<string, PolicyConfig>
     *
     * @throws PolicyException when policies are invalid
     */
    public function read($value): array
    {
        if (!is_array($value) || $value === []) {
            throw new PolicyException('Invalid loc.yaml: "policies" must be a non-empty mapping.');
        }

        $definitions = [];
        foreach ($value as $name => $policy) {
            if (!is_string($name) || $name === '') {
                throw new PolicyException('Invalid loc.yaml: every policy must have a non-empty string name.');
            }
            $definitions[$name] = $this->policyConfigReader->read($name, $policy);
        }

        return $this->policyResolver->resolve($definitions);
    }
}
