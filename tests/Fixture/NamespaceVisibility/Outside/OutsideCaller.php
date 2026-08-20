<?php

declare(strict_types=1);

namespace Tests\Fixture\NamespaceVisibility\Outside;

use Tests\Fixture\NamespaceVisibility\Package\FriendScoped;
use Tests\Fixture\NamespaceVisibility\Package\MemberScoped;
use Tests\Fixture\NamespaceVisibility\Package\NamespaceScoped;
use Tests\Fixture\NamespaceVisibility\Package\ParentScoped;
use Tests\Fixture\NamespaceVisibility\Package\PublicScoped;
use Tests\Fixture\NamespaceVisibility\Package\RootScoped;

final class OutsideCaller
{
    public function instantiateScopedClass(): int
    {
        $scoped = new NamespaceScoped();

        return $scoped->run();
    }

    public function readScopedConstant(): int
    {
        return NamespaceScoped::LIMIT;
    }

    public function readScopedProperty(NamespaceScoped $scoped): int
    {
        return $scoped->counter;
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

    public function callScopedMembers(MemberScoped $scoped): int
    {
        $call = $scoped->internalRun();
        $property = $scoped->state;
        $staticProperty = MemberScoped::$sharedState;

        return $call + $property + $staticProperty + $scoped->publicRun();
    }

    public function readScopedMemberConstant(): string
    {
        return MemberScoped::SECRET;
    }

    public function callPermittedScopes(ParentScoped $parentScoped, RootScoped $rootScoped, FriendScoped $friendScoped, PublicScoped $publicScoped): int
    {
        return $parentScoped->run() + $rootScoped->run() + $friendScoped->run() + $publicScoped->run();
    }
}
