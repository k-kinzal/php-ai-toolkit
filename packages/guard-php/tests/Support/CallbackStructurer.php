<?php

declare(strict_types=1);

namespace Tests\Support;

use Closure;
use Guard\Structure\Source;
use Guard\Structure\Structurer;
use Guard\Structure\Subject;

/** Exercises third-party structurers using a callback. */
final class CallbackStructurer implements Structurer
{
    /** @param Closure(Source): Subject $callback */
    public function __construct(private Closure $callback)
    {
    }
    public function structure(Source $source): Subject
    {
        return ($this->callback)($source);
    }
}
