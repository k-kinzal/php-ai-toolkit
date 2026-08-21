<?php

declare(strict_types=1);

namespace Tests\Fixture\ScopeGuard\Package;

/**
 * @visibility public
 */
final class PublicScoped
{
    public function run(): int
    {
        return 1;
    }
}
