<?php

declare(strict_types=1);

namespace Tests\Fixture\VisibilityScope\Outside;

use Tests\Fixture\VisibilityScope\Package\NamespaceScoped;
use Tests\Fixture\VisibilityScope\Package\ScopedBase;
use Tests\Fixture\VisibilityScope\Package\ScopedContract;
use Tests\Fixture\VisibilityScope\Package\ScopedTrait;

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
