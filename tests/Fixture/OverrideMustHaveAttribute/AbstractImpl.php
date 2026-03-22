<?php

declare(strict_types=1);

namespace Tests\Fixture\OverrideMustHaveAttribute;

abstract class AbstractBase
{
    abstract public function process(): string;
}

class AbstractImpl extends AbstractBase
{
    public function process(): string
    {
        return 'done';
    }
}
