<?php

declare(strict_types=1);

namespace Tests\Fixture\ScopeGuard\Invalid;

/**
 * @visibility parrent
 */
final class MalformedTags
{
    /**
     * @visibility public
     * @visibility namespace
     */
    public const MIXED = 1;

    /**
     * @visibility 123bad
     */
    public function unusable(): int
    {
        return 1;
    }
}
