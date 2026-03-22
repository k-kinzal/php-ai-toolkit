<?php

declare(strict_types=1);

namespace Tests\Fixture\OverrideMustHaveAttribute;

class AnotherBase
{
    public function doSomething(): void
    {
    }
}

class WithAttribute extends AnotherBase
{
    #[\Override]
    public function doSomething(): void
    {
    }
}
