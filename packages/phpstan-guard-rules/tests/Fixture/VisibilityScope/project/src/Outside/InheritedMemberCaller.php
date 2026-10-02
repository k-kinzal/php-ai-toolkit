<?php

declare(strict_types=1);

namespace Tests\Fixture\VisibilityScope\Outside;

final class InheritedMemberCaller
{
    public function callInheritedScopedMethod(): int
    {
        return OutsideInheritor::base();
    }
}
