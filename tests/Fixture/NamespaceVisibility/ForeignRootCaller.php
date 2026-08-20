<?php

declare(strict_types=1);

namespace NamespaceVisibilityForeignRoot;

use Tests\Fixture\NamespaceVisibility\Package\ParentScoped;
use Tests\Fixture\NamespaceVisibility\Package\RootScoped;

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
