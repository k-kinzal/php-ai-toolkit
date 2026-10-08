<?php

declare(strict_types=1);

namespace Guard\Execution;

use Guard\Collect\Collector;
use Guard\Config\ComponentLoader;
use Guard\Config\Configuration;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Policy\Context;
use Guard\Policy\Plan;
use Guard\Policy\Policy;
use Guard\Policy\PolicyBinding;
use Guard\Repair\ChangeSet;
use JsonException;
use RuntimeException;

/**
 * Merges input declarations, collects once, evaluates policies and combines reportable plans.
 */
final class Pipeline
{
    /**
     * Creates the Pipeline with its declared dependencies.
     */
    public function __construct(private ?Registry $registry = null, private ?Collector $collector = null)
    {
    }
    /** Runs policies only after their requested file structures have been collected.
     * @throws RuntimeException
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function run(Configuration $configuration, string $configPath, bool $repair = false): Plan
    {
        $context = new Context($configuration->root, $configPath, $repair, $configuration->scope);
        $registry = $this->registry === null ? Registry::defaults($configuration->policies) : clone $this->registry;
        foreach ($configuration->extensions as $class => $options) {
            $component = (new ComponentLoader())->create($class, $options);
            if ($component instanceof Policy) {
                $registry->addPolicy($class, $component);
            } else {
                $registry->addStructure($class, $component);
            }
        }
        /** @var array<string, Input> $requests */
        $requests = [];
        /** @var array<int, array<string, string>> $names */
        $names = [];
        $bindings = $registry->policies();
        foreach ($bindings as $index => $binding) {
            $names[$index] = [];
            foreach ($binding->policy->inputs($context) as $name => $input) {
                $key = $index . ':' . $name;
                $requests[$key] = $input;
                $names[$index][$name] = $key;
            }
        }
        $collected = ($this->collector ?? new Collector())->collect($configuration->root, $requests, $registry->structures(), $configuration->scope);
        $plans = [];
        foreach ($bindings as $index => $binding) {
            $sets = [];
            foreach ($names[$index] as $name => $key) {
                $sets[$name] = $collected[$key];
            }
            $inputs = new InputSet($sets);
            $inputs->validate();
            $plans[$binding->id] = $binding->policy->evaluate($inputs, $context);
        }
        usort($bindings, static fn (PolicyBinding $a, PolicyBinding $b): int => $a->reportOrder <=> $b->reportOrder);
        $findings = [];
        $changes = new ChangeSet();
        $blocking = [];
        foreach ($bindings as $binding) {
            $plan = $plans[$binding->id];
            $findings = array_merge($findings, $plan->findings);
            foreach ($plan->changes as $change) {
                $changes->add($change);
            }
            $blocking = array_merge($blocking, $plan->blockingFindings);
        }
        return new Plan($findings, $changes->changes(), $blocking);
    }
}
