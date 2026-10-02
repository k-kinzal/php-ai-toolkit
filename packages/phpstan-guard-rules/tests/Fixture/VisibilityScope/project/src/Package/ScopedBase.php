<?php

declare(strict_types=1);

namespace Tests\Fixture\VisibilityScope\Package;

/**
 * @visibility namespace
 */
abstract class ScopedBase
{
    /**
     * @visibility namespace
     */
    public function base(): int
    {
        return 1;
    }
}
