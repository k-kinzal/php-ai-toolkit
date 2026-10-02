<?php

declare(strict_types=1);

namespace Toolkit\Guard\Execution;

use JsonException;
use Toolkit\Guard\Config\Configuration;
use Toolkit\Guard\Policy\PolicyException;
use Toolkit\Guard\Policy\Rule;

/**
 * Builds one complete plan before allowing configuration writes.
 */
final class ConfigurationPlanner
{
    /**
     * @throws PolicyException when a policy tries to change itself
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function plan(Configuration $config, string $configPath, bool $repair): Plan
    {
        /** @var array<string, non-empty-list<Rule>> $groups */
        $groups = [];
        foreach ($config->rules as $rule) {
            $path = (new TargetPath())->resolve($config->root, $rule->file);
            if (realpath($path) === realpath($configPath)) {
                throw new PolicyException('Rule ' . $rule->id . ' targets guard.yaml itself. Policies cannot rewrite their own constraints.');
            }
            $groups[$path][] = $rule;
        }
        $findings = [];
        $changes = [];
        foreach ($groups as $path => $rules) {
            $plan = (new FilePlanner())->plan($path, $rules, $repair);
            $findings = array_merge($findings, $plan->findings);
            $changes = array_merge($changes, $plan->changes);
        }
        return new Plan($findings, $changes);
    }
}
