<?php

declare(strict_types=1);

namespace Tests\Fixture\NamespaceVisibility\Package;

/**
 * @visibility namespace
 */
trait ScopedTrait
{
    public function shared(): int
    {
        return 1;
    }
}
