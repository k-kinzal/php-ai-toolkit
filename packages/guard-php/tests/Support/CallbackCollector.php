<?php

declare(strict_types=1);

namespace Tests\Support;

use Closure;
use Guard\Collect\Collector;
use Guard\Collect\Subject;
use Guard\Execution\Context;

final class CallbackCollector implements Collector
{
    /**
     * @param Closure(Context): list<Subject> $callback
     */
    public function __construct(private Closure $callback)
    {
    }
    /**
     * @return list<Subject>
     */
    public function collect(Context $context): array
    {
        return ($this->callback)($context);
    }
}
