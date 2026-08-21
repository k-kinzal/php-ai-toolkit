<?php

declare(strict_types=1);

namespace Tests\Fixture\ScopeGuard\Outside;

use Tests\Fixture\ScopeGuard\Package\MemberScoped;
use Tests\Fixture\ScopeGuard\Package\NamespaceScoped;
use Tests\Fixture\ScopeGuard\Package\ParentScoped;
use Tests\Fixture\ScopeGuard\Package\PublicScoped;
use Tests\Fixture\ScopeGuard\Package\RootScoped;

final class OutsideCaller
{
    public function instantiateScopedClass(): int
    {
        return (new NamespaceScoped())->counter;
    }

    public function readScopedConstant(): int
    {
        return NamespaceScoped::LIMIT;
    }

    public function readScopedStaticProperty(): int
    {
        return NamespaceScoped::$shared;
    }

    public function callScopedStaticMethod(): int
    {
        return NamespaceScoped::make()->counter;
    }

    public function checkScopedInstance(object $candidate): bool
    {
        return $candidate instanceof NamespaceScoped;
    }

    public function nameScopedClass(): string
    {
        return NamespaceScoped::class;
    }

    public function readScopedMemberConstant(): string
    {
        return MemberScoped::SECRET;
    }

    public function readScopedMemberProperty(): int
    {
        return MemberScoped::$sharedState;
    }

    public function callPermittedScopes(ParentScoped $parentScoped, RootScoped $rootScoped, PublicScoped $publicScoped): int
    {
        return $parentScoped->run() + $rootScoped->run() + $publicScoped->run();
    }
}
