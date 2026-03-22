<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbiddenMagicMethodCall;

class NormalCall
{
    public function run(): void
    {
        $this->doSomething();
    }

    public function doSomething(): void
    {
    }
}
