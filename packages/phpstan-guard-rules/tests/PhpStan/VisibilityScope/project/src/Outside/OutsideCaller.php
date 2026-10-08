<?php

declare(strict_types=1);

namespace Tests\PhpStan\VisibilityScope\Outside;

use Tests\PhpStan\VisibilityScope\Package\MemberScoped;
use Tests\PhpStan\VisibilityScope\Package\NamespaceScoped;
use Tests\PhpStan\VisibilityScope\Package\ParentScoped;
use Tests\PhpStan\VisibilityScope\Package\PublicScoped;
use Tests\PhpStan\VisibilityScope\Package\RootScoped;

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
