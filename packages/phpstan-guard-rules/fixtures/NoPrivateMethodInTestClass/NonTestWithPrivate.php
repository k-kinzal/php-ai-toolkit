<?php

declare(strict_types=1);

namespace App\Fixture\NoPrivateMethodInTestClass;

class NonTestWithPrivate
{
    public function run(): string
    {
        return $this->helper();
    }

    private function helper(): string
    {
        return 'hello';
    }
}
