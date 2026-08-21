<?php

declare(strict_types=1);

namespace Tests\Fixture\ScopeGuard\Package;

enum ScopedSuit: string
{
    /**
     * @visibility namespace
     */
    case Hearts = 'hearts';

    case Spades = 'spades';
}
