<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Config\Policy;

use function array_keys;
use function implode;
use function sprintf;

use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\LocGuardException;

/**
 * Resolves policy inheritance into effective metric limits.
 */
final class PolicyResolver
{
    /**
     * Resolves all definitions without order-dependent inheritance.
     *
     * @param array<string, PolicyDefinition> $definitions
     * @return array<string, PolicyConfig>
     *
     * @throws LocGuardException when a parent is missing, inheritance cycles, or all checks are disabled
     */
    public function resolve(array $definitions): array
    {
        $this->validateParents($definitions);

        $unresolved = $definitions;
        $resolved = [];
        while ($unresolved !== []) {
            $progress = false;
            foreach ($unresolved as $name => $definition) {
                if ($definition->extends !== null && !isset($resolved[$definition->extends])) {
                    continue;
                }

                $resolved[$name] = $this->resolveDefinition($definition, $resolved);
                unset($unresolved[$name]);
                $progress = true;
            }

            if (!$progress) {
                throw new LocGuardException(sprintf(
                    'Invalid loc.yaml: policy inheritance cycle detected among: %s.',
                    implode(', ', array_keys($unresolved)),
                ));
            }
        }

        return $resolved;
    }

    /**
     * Ensures that every configured parent policy exists.
     *
     * @param array<string, PolicyDefinition> $definitions
     *
     * @throws LocGuardException when a policy names an unknown parent
     */
    public function validateParents(array $definitions): void
    {
        foreach ($definitions as $definition) {
            if ($definition->extends !== null && !isset($definitions[$definition->extends])) {
                throw new LocGuardException(sprintf(
                    'Invalid loc.yaml: policy "%s" extends unknown policy "%s".',
                    $definition->name,
                    $definition->extends,
                ));
            }
        }
    }

    /**
     * Applies one definition to its already resolved parent limits.
     *
     * @param array<string, PolicyConfig> $resolved
     *
     * @throws LocGuardException when the resulting policy enables no checks
     */
    public function resolveDefinition(PolicyDefinition $definition, array $resolved): PolicyConfig
    {
        $values = $definition->extends === null
            ? LimitConfig::disabled()->values()
            : $resolved[$definition->extends]->limits->values();
        foreach ($definition->limitPatch as $key => $value) {
            $values[$key] = $value;
        }

        $limits = LimitConfig::fromValues($values);
        if (!$limits->hasEnabledLimit()) {
            throw new LocGuardException(sprintf(
                'Invalid loc.yaml: policy "%s" must enable at least one metric limit.',
                $definition->name,
            ));
        }

        return new PolicyConfig($definition->name, $definition->extends, $limits);
    }
}
