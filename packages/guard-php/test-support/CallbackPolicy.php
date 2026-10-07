<?php

declare(strict_types=1);

namespace Tests\Support;

use Closure;
use Guard\Collect\Input;
use Guard\Collect\InputSet;
use Guard\Execution\Context;
use Guard\Execution\Plan;
use Guard\Policy\Policy;

/** Exercises extensions with explicit inputs and a policy callback. */
final class CallbackPolicy implements Policy
{
    /** @param array<string, Input> $inputs
     * @param Closure(InputSet, Context): Plan $callback
     */
    public function __construct(private array $inputs, private Closure $callback)
    {
    }
    public function inputs(Context $context): array
    {
        return $this->inputs;
    }
    public function evaluate(InputSet $inputs, Context $context): Plan
    {
        return ($this->callback)($inputs, $context);
    }
}
