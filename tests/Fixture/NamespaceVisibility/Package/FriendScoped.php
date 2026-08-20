<?php

declare(strict_types=1);

namespace Tests\Fixture\NamespaceVisibility\Package;

/**
 * @visibility Tests\Fixture\NamespaceVisibility\Outside
 */
final class FriendScoped
{
    public function run(): int
    {
        return 1;
    }
}
