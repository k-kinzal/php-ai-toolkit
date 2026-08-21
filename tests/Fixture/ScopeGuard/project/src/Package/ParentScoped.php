<?php

declare(strict_types=1);

namespace Tests\Fixture\ScopeGuard\Package;

/**
 * @visibility parent
 */
final class ParentScoped
{
    public function run(): int
    {
        return 1;
    }
}
