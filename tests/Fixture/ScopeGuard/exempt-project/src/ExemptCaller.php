<?php

declare(strict_types=1);

namespace ScopeGuardExemptFixture\Outside;

use ScopeGuardExemptFixture\Package\Scoped;

final class ExemptCaller
{
    public function run(): int
    {
        return (new Scoped())->run();
    }
}
