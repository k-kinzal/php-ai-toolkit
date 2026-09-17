<?php

declare(strict_types=1);

namespace Tests\Fixture\NoNonPublicMethod;

class WithProtectedConstructor
{
    protected function __construct(
        public readonly string $name,
    ) {
    }

    public static function named(string $name): static
    {
        return new static($name);
    }
}
