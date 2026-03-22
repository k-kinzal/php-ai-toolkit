<?php

declare(strict_types=1);

namespace App\Fixture\NoTraitUseInTestClass;

trait SomeTrait
{
    public function helperMethod(): string
    {
        return 'hello';
    }
}

class NonTestWithTrait
{
    use SomeTrait;

    public function run(): string
    {
        return $this->helperMethod();
    }
}
