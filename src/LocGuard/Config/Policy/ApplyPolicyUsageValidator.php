<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Config\Policy;

use function array_keys;
use function array_pop;
use function implode;
use function sprintf;

use Toolkit\LocGuard\LocGuardException;

/**
 * Validates policy references and rejects policies that can never be selected.
 */
final class ApplyPolicyUsageValidator
{
    /**
     * @param list<ApplyRuleConfig> $rules
     * @param array<string, PolicyConfig> $policies
     *
     * @throws LocGuardException when a policy is unknown or unused
     */
    public function validate(string $defaultPolicy, array $rules, array $policies): void
    {
        $referenced = [$defaultPolicy => true];
        foreach ($rules as $rule) {
            $referenced[$rule->policy] = true;
        }
        foreach (array_keys($referenced) as $policy) {
            if (!isset($policies[$policy])) {
                throw new LocGuardException(sprintf('Invalid loc.yaml: apply references unknown policy "%s".', $policy));
            }
        }

        $pending = array_keys($referenced);
        while ($pending !== []) {
            $policy = array_pop($pending);
            $parent = $policies[$policy]->extends;
            if ($parent !== null && !isset($referenced[$parent])) {
                $referenced[$parent] = true;
                $pending[] = $parent;
            }
        }

        $unused = [];
        foreach (array_keys($policies) as $name) {
            if (!isset($referenced[$name])) {
                $unused[] = $name;
            }
        }
        if ($unused !== []) {
            throw new LocGuardException(sprintf(
                'Invalid loc.yaml: unused policies: %s. Assign or remove them.',
                implode(', ', $unused),
            ));
        }
    }
}
