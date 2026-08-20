<?php

declare(strict_types=1);

namespace Tests\Fixture\NamespaceVisibility\Invalid;

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
    public int $value = 0;

    /**
     * @visibility public
     */
    public function documented(): int
    {
        return 1;
    }
}
