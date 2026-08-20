<?php

declare(strict_types=1);

namespace Tests\Fixture\NamespaceVisibility\Package;

/**
 * @visibility namespace
 */
abstract class ScopedBase
{
    public function base(): int
    {
        return 1;
    }
}
