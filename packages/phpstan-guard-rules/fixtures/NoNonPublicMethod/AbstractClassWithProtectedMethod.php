<?php

declare(strict_types=1);

namespace Tests\Fixture\NoNonPublicMethod;

abstract class AbstractClassWithProtectedMethod
{
    public function run(): string
    {
        return $this->templateStep();
    }

    protected function templateStep(): string
    {
        return 'done';
    }
}
