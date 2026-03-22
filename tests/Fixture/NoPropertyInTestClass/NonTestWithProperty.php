<?php

declare(strict_types=1);

namespace App\Fixture\NoPropertyInTestClass;

class NonTestWithProperty
{
    private string $name = 'Alice';

    public function getName(): string
    {
        return $this->name;
    }
}
