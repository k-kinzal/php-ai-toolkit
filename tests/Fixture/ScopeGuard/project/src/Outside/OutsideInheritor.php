<?php

declare(strict_types=1);

namespace Tests\Fixture\ScopeGuard\Outside;

use Tests\Fixture\ScopeGuard\Package\NamespaceScoped;
use Tests\Fixture\ScopeGuard\Package\ScopedBase;
use Tests\Fixture\ScopeGuard\Package\ScopedContract;
use Tests\Fixture\ScopeGuard\Package\ScopedTrait;

final class OutsideInheritor extends ScopedBase implements ScopedContract
{
    use ScopedTrait;

    public ?NamespaceScoped $held = null;

    public function run(): int
    {
        return parent::base();
    }

    public function handle(?NamespaceScoped $scoped): ?NamespaceScoped
    {
        return $scoped;
    }
}
