<?php

declare(strict_types=1);

namespace Tests\Fixture\NamespaceVisibility\Package\Nested;

use Tests\Fixture\NamespaceVisibility\Package\MemberScoped;
use Tests\Fixture\NamespaceVisibility\Package\NamespaceScoped;

final class NestedCaller
{
    public function useScopedClass(NamespaceScoped $scoped): int
    {
        return $scoped->run() + NamespaceScoped::LIMIT;
    }

    public function useScopedMember(MemberScoped $scoped): int
    {
        return $scoped->internalRun();
    }
}
