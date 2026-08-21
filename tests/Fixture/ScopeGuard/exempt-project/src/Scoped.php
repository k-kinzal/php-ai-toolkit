<?php

declare(strict_types=1);

namespace ScopeGuardExemptFixture\Package;

/**
 * @visibility namespace
 */
final class Scoped
{
    public function run(): int
    {
        return 1;
    }
}
