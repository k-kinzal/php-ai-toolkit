<?php

declare(strict_types=1);

namespace Tests\Fixture\ScopeGuard\Package;

/**
 * @visibility namespace
 */
abstract class ScopedBase
{
    /**
     * @visibility namespace
     */
    public function base(): int
    {
        return 1;
    }
}
