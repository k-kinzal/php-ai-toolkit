<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture;

final class TestClassInRestrictedNamespace
{
    public string $name;

    public const STATUS = 'active';

    public function doSomething(): void
    {
    }
}
