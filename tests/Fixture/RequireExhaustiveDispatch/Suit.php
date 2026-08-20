<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireExhaustiveDispatch;

enum Suit: string
{
    case Hearts = 'hearts';
    case Diamonds = 'diamonds';
    case Spades = 'spades';
    case Clubs = 'clubs';
}
