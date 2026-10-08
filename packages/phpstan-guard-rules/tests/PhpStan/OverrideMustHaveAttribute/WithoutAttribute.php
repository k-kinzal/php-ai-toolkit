<?php

declare(strict_types=1);

namespace Tests\PhpStan\OverrideMustHaveAttribute;

class BaseClass
{
    public function doSomething(): void
    {
    }
}

class WithoutAttribute extends BaseClass
{
    public function doSomething(): void
    {
    }
}
