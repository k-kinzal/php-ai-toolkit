<?php

declare(strict_types=1);

namespace Tests\Fixture\VisibilityScope\Package;

final class InsideCaller
{
    public function useScopedClass(): int
    {
        $scoped = new NamespaceScoped();

        return $scoped->run() + NamespaceScoped::LIMIT + NamespaceScoped::$shared;
    }

    public function useScopedMembers(MemberScoped $scoped): string
    {
        return MemberScoped::SECRET . $scoped->internalRun() . ScopedSuit::Hearts->value;
    }
}
