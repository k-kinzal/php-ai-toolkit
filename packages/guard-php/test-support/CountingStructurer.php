<?php

declare(strict_types=1);

namespace Tests\Support;

use Guard\Structure\Source;
use Guard\Structure\Structurer;
use Guard\Structure\Subject;

/** Counts executions of a real parser, including dependent structure requests. */
final class CountingStructurer implements Structurer
{
    public int $calls = 0;
    public function __construct(private Structurer $inner)
    {
    }
    public function structure(Source $source): Subject
    {
        $this->calls++;
        return $this->inner->structure($source);
    }
}
