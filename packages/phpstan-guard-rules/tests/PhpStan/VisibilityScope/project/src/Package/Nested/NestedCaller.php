<?php

declare(strict_types=1);

namespace Tests\PhpStan\VisibilityScope\Package\Nested;

use Tests\PhpStan\VisibilityScope\Package\NamespaceScoped;

final class NestedCaller
{
    public function useScopedClass(NamespaceScoped $scoped): int
    {
        return $scoped->run() + NamespaceScoped::LIMIT;
    }
}
