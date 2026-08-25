<?php

declare(strict_types=1);

namespace Tests\Fixture\VisibilityScope\Package;

final class MemberScoped
{
    /**
     * @visibility namespace
     */
    public const SECRET = 'hidden';

    /**
     * @visibility namespace
     */
    public static int $sharedState = 0;

    /**
     * @visibility namespace
     */
    public function internalRun(): int
    {
        return 1;
    }

    public function publicRun(): int
    {
        return 2;
    }
}
