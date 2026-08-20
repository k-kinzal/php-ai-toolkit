<?php

declare(strict_types=1);

namespace Tests\Fixture\NamespaceVisibility\Outside;

use Tests\Fixture\NamespaceVisibility\Package\NamespaceScoped;
use Tests\Fixture\NamespaceVisibility\Package\ScopedBase;
use Tests\Fixture\NamespaceVisibility\Package\ScopedContract;
use Tests\Fixture\NamespaceVisibility\Package\ScopedTrait;

final class OutsideInheritor extends ScopedBase implements ScopedContract
{
    use ScopedTrait;

    public ?NamespaceScoped $held = null;

    public function run(): int
    {
        return 1;
    }

    public function handle(?NamespaceScoped $scoped): ?NamespaceScoped
    {
        return $scoped;
    }

    public function callInheritedBase(): int
    {
        return parent::base();
    }
}
