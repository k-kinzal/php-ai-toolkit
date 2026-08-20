<?php

declare(strict_types=1);

namespace Tests\Fixture\NamespaceVisibility\Package;

final class MemberScoped
{
    /**
     * @visibility namespace
     */
    public const SECRET = 'hidden';

    /**
     * @visibility namespace
     */
    public int $state = 0;

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

    /**
     * @visibility namespace
     */
    public static function internalBuild(): self
    {
        return new self();
    }

    public function publicRun(): int
    {
        return 2;
    }
}
