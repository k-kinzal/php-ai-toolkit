<?php

declare(strict_types=1);

namespace Guard\Execution;

use Guard\Extension\BuiltinExtension;
use Guard\Extension\Registry;
use JsonException;

/**
 * Collects information, dispatches registered policies and combines reportable results.
 */
final class Pipeline
{
    private Registry $registry;
    /**
     * Accepts a complete registry; the default registry contains the built-in extension.
     */
    public function __construct(?Registry $registry = null)
    {
        if ($registry === null) {
            $registry = new Registry();
            (new BuiltinExtension())->register($registry);
        }
        $this->registry = $registry;
    }
    /**
     * Applies matching policies to each collected subject before reporting any results.
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     * @throws \Guard\Policy\PolicyException
     */
    public function run(Context $context): Plan
    {
        /** @var array<string, list<Plan>> $plans */
        $plans = [];
        foreach ($this->registry->collectors() as $collector) {
            foreach ($collector->collect($context) as $information) {
                foreach ($this->registry->policies() as $binding) {
                    $type = $binding->informationType;
                    if ($information instanceof $type) {
                        $plans[$binding->id][] = $binding->policy->evaluate($information, $context);
                    }
                }
            }
        }
        $findings = [];
        $changes = new ChangeSet();
        $blocking = [];
        foreach ($this->registry->policies() as $binding) {
            foreach ($plans[$binding->id] ?? [] as $plan) {
                $findings = array_merge($findings, $plan->findings);
                foreach ($plan->changes as $change) {
                    $changes->add($change);
                }
                $blocking = array_merge($blocking, $plan->blockingFindings);
            }
        }
        return new Plan($findings, $changes->changes(), $blocking);
    }
}
