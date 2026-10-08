<?php

declare(strict_types=1);

namespace Tests\PhpStan\VisibilityScope\Package;

/**
 * @visibility public
 */
final class PublicScoped
{
    public function run(): int
    {
        return 1;
    }
}
