<?php

declare(strict_types=1);

namespace Tests\Fixture\NoNonPublicMethod;

class WithProtectedMethod
{
    public function run(): string
    {
        return $this->helper();
    }

    protected function helper(): string
    {
        return 'done';
    }
}
