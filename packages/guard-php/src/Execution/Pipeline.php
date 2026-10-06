<?php

declare(strict_types=1);

namespace Guard\Execution;

use Guard\Collect\Collector;
use Guard\Collect\Input;
use Guard\Collect\InputSet;
use Guard\Extension\BuiltinExtension;
use Guard\Extension\ExtensionLoader;
use Guard\Extension\PolicyBinding;
use Guard\Extension\Registry;
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
    public function run(Context $context): Plan
    {
        $registry = $this->registry === null ? new Registry() : clone $this->registry;
        if ($this->registry === null) {
            (new BuiltinExtension($context->configuration))->register($registry);
        }
        (new ExtensionLoader())->register($context->configuration->extensions, $registry);
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
        $collected = ($this->collector ?? new Collector())->collect($context->configuration->root, $requests, $registry->structures(), $context->configuration->scope);
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
