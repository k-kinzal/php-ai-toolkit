<?php

declare(strict_types=1);

namespace Tests\PhpStan\NoNonPublicMethod;

trait TraitWithProtectedMethod
{
    protected function templateStep(): string
    {
        return 'done';
    }
}
