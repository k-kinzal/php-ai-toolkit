<?php

declare(strict_types=1);

namespace Tests\Fixture\NamespaceVisibility\Package;

final class InsideCaller
{
    public function useScopedClass(): int
    {
        $scoped = new NamespaceScoped();

        return $scoped->run() + $scoped->counter + NamespaceScoped::LIMIT + NamespaceScoped::$shared;
    }

    public function useScopedMembers(): int
    {
        $scoped = new MemberScoped();

        return $scoped->internalRun() + $scoped->state + MemberScoped::$sharedState;
    }

    public function useScopedEnum(): string
    {
        return ScopedSuit::Hearts->value;
    }

    public function useStaticFactory(): NamespaceScoped
    {
        return NamespaceScoped::make();
    }
}
