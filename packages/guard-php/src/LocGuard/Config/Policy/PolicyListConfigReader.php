<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Config\Policy;

use function is_array;
use function is_string;

use Toolkit\LocGuard\LocGuardException;

/**
 * Reads and resolves all named LocGuard policies.
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
     * @throws LocGuardException when policies are invalid
     */
    public function read($value): array
    {
        if (!is_array($value) || $value === []) {
            throw new LocGuardException('Invalid loc.yaml: "policies" must be a non-empty mapping.');
        }

        $definitions = [];
        foreach ($value as $name => $policy) {
            if (!is_string($name) || $name === '') {
                throw new LocGuardException('Invalid loc.yaml: every policy must have a non-empty string name.');
            }
            $definitions[$name] = $this->policyConfigReader->read($name, $policy);
        }

        return $this->policyResolver->resolve($definitions);
    }
}
