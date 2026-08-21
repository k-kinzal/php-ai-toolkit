<?php

declare(strict_types=1);

namespace Tests\Fixture\ScopeGuard\Package\Nested;

use Tests\Fixture\ScopeGuard\Package\NamespaceScoped;

final class NestedCaller
{
    public function useScopedClass(NamespaceScoped $scoped): int
    {
        return $scoped->run() + NamespaceScoped::LIMIT;
    }
}
