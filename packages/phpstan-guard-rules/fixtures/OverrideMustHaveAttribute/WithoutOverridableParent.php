<?php

declare(strict_types=1);

namespace Tests\Fixture\OverrideMustHaveAttribute;

class ParentWithConstructorAndPrivate
{
    public function __construct(string $name)
    {
        unset($name);
    }

    private function hidden(): void
    {
    }

    public function visible(): void
    {
    }
}

class WithoutOverridableParent extends ParentWithConstructorAndPrivate
{
    public function __construct(string $name)
    {
        parent::__construct($name);
    }

    public function hidden(): void
    {
    }
}
