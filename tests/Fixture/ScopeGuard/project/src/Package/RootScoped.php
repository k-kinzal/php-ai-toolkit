<?php

declare(strict_types=1);

namespace Tests\Fixture\ScopeGuard\Package;

/**
 * @visibility root
 */
final class RootScoped
{
    public function run(): int
    {
        return 1;
    }
}
