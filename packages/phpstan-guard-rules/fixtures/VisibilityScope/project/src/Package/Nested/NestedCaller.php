<?php

declare(strict_types=1);

namespace Tests\Fixture\VisibilityScope\Package\Nested;

use Tests\Fixture\VisibilityScope\Package\NamespaceScoped;

final class NestedCaller
{
    public function useScopedClass(NamespaceScoped $scoped): int
    {
        return $scoped->run() + NamespaceScoped::LIMIT;
    }
}
