<?php

declare(strict_types=1);

namespace Tests\PhpStan\VisibilityScope\Outside;

use Tests\PhpStan\VisibilityScope\Package\NamespaceScoped;
use Tests\PhpStan\VisibilityScope\Package\ScopedBase;
use Tests\PhpStan\VisibilityScope\Package\ScopedContract;
use Tests\PhpStan\VisibilityScope\Package\ScopedTrait;

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
