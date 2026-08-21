<?php

declare(strict_types=1);

namespace Tests\Fixture\ScopeGuard\Package;

/**
 * @visibility namespace
 */
trait ScopedTrait
{
    public function shared(): int
    {
        return 1;
    }
}
