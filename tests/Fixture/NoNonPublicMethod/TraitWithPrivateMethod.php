<?php

declare(strict_types=1);

namespace Tests\Fixture\NoNonPublicMethod;

trait TraitWithPrivateMethod
{
    public function run(): string
    {
        return $this->helper();
    }

    private function helper(): string
    {
        return 'done';
    }
}
