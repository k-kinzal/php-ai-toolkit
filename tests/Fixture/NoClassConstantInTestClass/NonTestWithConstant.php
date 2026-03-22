<?php

declare(strict_types=1);

namespace App\Fixture\NoClassConstantInTestClass;

class NonTestWithConstant
{
    private const FOO = 'bar';

    public function getFoo(): string
    {
        return self::FOO;
    }
}
