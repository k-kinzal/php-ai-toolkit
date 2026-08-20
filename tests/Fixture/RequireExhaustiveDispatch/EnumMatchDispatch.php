<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireExhaustiveDispatch;

final class EnumMatchDispatch
{
    public function partialWithDefault(Suit $suit): string
    {
        return match ($suit) {
            Suit::Hearts, Suit::Diamonds => 'red',
            default => 'black',
        };
    }

    public function partialWithoutDefault(Suit $suit): string
    {
        return match ($suit) {
            Suit::Hearts => 'red',
            Suit::Diamonds => 'red',
            Suit::Spades => 'black',
        };
    }

    public function completeWithDefault(Suit $suit): string
    {
        return match ($suit) {
            Suit::Hearts => 'red',
            Suit::Diamonds => 'red',
            Suit::Spades => 'black',
            Suit::Clubs => 'black',
            default => 'unknown',
        };
    }

    public function nullableWithDefault(?Suit $suit): string
    {
        return match ($suit) {
            null => 'none',
            Suit::Hearts, Suit::Diamonds => 'red',
            Suit::Spades => 'black',
            default => 'unknown',
        };
    }

    public function narrowedSubjectWithDefault(Suit $suit): string
    {
        if ($suit === Suit::Spades || $suit === Suit::Clubs) {
            return 'black';
        }

        return match ($suit) {
            Suit::Hearts => 'hearts',
            Suit::Diamonds => 'diamonds',
            default => 'unknown',
        };
    }
}
