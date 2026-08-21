<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireExhaustiveDispatch;

final class EnumMatchDispatch
{
    private Suit $suit;

    public function __construct(Suit $suit)
    {
        $this->suit = $suit;
    }

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

    public function propertySubjectMatch(): string
    {
        return match ($this->suit) {
            Suit::Hearts => 'hearts',
            default => 'other',
        };
    }

    public function methodCallSubjectMatch(): string
    {
        return match ($this->suit()) {
            Suit::Hearts => 'hearts',
            default => 'other',
        };
    }

    public function staticCallSubjectMatch(): string
    {
        return match (self::pick()) {
            Suit::Hearts => 'hearts',
            default => 'other',
        };
    }

    public function arrayOffsetSubjectMatch(Suit $first, Suit $second): string
    {
        $suits = [$first, $second];

        return match ($suits[0]) {
            Suit::Hearts => 'hearts',
            default => 'other',
        };
    }

    public function conditionFormMatch(Suit $suit): string
    {
        return match (true) {
            $suit === Suit::Hearts => 'hearts',
            default => 'other',
        };
    }

    public function inArrayConditionMatch(Suit $suit): string
    {
        return match (true) {
            in_array($suit, [Suit::Hearts, Suit::Diamonds], true) => 'red',
            default => 'other',
        };
    }

    public function suit(): Suit
    {
        return $this->suit;
    }

    public static function pick(): Suit
    {
        return Suit::Hearts;
    }
}
