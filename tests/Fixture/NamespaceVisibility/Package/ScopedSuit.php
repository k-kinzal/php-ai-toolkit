<?php

declare(strict_types=1);

namespace Tests\Fixture\NamespaceVisibility\Package;

/**
 * @visibility namespace
 */
enum ScopedSuit: string
{
    case Hearts = 'hearts';
    case Spades = 'spades';
}
