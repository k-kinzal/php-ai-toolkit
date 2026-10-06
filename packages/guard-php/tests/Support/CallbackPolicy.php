<?php

declare(strict_types=1);

namespace Tests\Support;

use Closure;
use Guard\Collect\Subject;
use Guard\Execution\Context;
use Guard\Execution\Plan;
use Guard\Policy\Policy;

final class CallbackPolicy implements Policy
{
    /**
     * @param Closure(Subject, Context): Plan $callback
     */
    public function __construct(private Closure $callback)
    {
    }
    public function evaluate(Subject $subject, Context $context): Plan
    {
        return ($this->callback)($subject, $context);
    }
}
