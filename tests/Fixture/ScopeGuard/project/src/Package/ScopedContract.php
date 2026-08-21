<?php

declare(strict_types=1);

namespace Tests\Fixture\ScopeGuard\Package;

/**
 * @visibility namespace
 */
interface ScopedContract
{
    public function run(): int;
}
