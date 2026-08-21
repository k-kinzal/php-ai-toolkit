<?php

declare(strict_types=1);

namespace ScopeGuardForeignRoot;

use Tests\Fixture\ScopeGuard\Package\ParentScoped;
use Tests\Fixture\ScopeGuard\Package\RootScoped;

final class ForeignRootCaller
{
    public function callRootScoped(RootScoped $scoped): int
    {
        return $scoped->run();
    }

    public function callParentScoped(ParentScoped $scoped): int
    {
        return $scoped->run();
    }
}
