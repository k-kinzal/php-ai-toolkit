<?php

declare(strict_types=1);

namespace App\Fixture\TestNamingConvention;

class NonTestClass
{
    public function testsomething(): void
    {
    }

    public function providerdata(): array
    {
        return [];
    }
}
