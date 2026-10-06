<?php

declare(strict_types=1);

namespace Tests\Support;

use Guard\Collect\Collector;
use Guard\Collect\InputSet;
use Guard\Execution\Context;
use Guard\Extension\BuiltinExtension;
use Guard\Extension\Registry;
use Guard\Policy\Policy;
use RuntimeException;

/** Prepares a policy snapshot for tests that remove the original files before evaluation. */
final class PreparedPolicy
{
    /** @return array{Policy, InputSet, Context} */
    public function prepare(Project $project, string $id, bool $repair = false): array
    {
        $context = $project->context($repair);
        $registry = new Registry();
        (new BuiltinExtension($context->configuration))->register($registry);
        foreach ($registry->policies() as $binding) {
            if ($binding->id === $id) {
                $inputs = new InputSet((new Collector())->collect($project->root, $binding->policy->inputs($context), $registry->structures()));
                $inputs->validate();
                return [$binding->policy, $inputs, $context];
            }
        }
        throw new RuntimeException('Test policy was not configured: ' . $id);
    }
}
