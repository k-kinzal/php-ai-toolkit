<?php

declare(strict_types=1);

namespace VisibilityScopeForeignRoot;

use Tests\PhpStan\VisibilityScope\Package\ParentScoped;
use Tests\PhpStan\VisibilityScope\Package\RootScoped;

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
