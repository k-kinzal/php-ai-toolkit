<?php

declare(strict_types=1);

namespace Tests\Fixture\VisibilityScope\Outside;

use Tests\Fixture\VisibilityScope\Package\MemberScoped;
use Tests\Fixture\VisibilityScope\Package\NamespaceScoped;
use Tests\Fixture\VisibilityScope\Package\ParentScoped;
use Tests\Fixture\VisibilityScope\Package\PublicScoped;
use Tests\Fixture\VisibilityScope\Package\RootScoped;

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
