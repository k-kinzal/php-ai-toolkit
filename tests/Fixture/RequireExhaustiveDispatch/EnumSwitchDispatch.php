<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireExhaustiveDispatch;

final class EnumSwitchDispatch
{
    public function partialWithoutDefault(Suit $suit): string
    {
        switch ($suit) {
            case Suit::Hearts:
                return 'red';
            case Suit::Diamonds:
                return 'red';
        }

        return 'unknown';
    }

    public function partialWithDefault(Suit $suit): string
    {
        switch ($suit) {
            case Suit::Hearts:
                return 'red';
            default:
                return 'black';
        }
    }

    public function complete(Suit $suit): string
    {
        switch ($suit) {
            case Suit::Hearts:
            case Suit::Diamonds:
                return 'red';
            case Suit::Spades:
            case Suit::Clubs:
                return 'black';
        }
    }

    public function completeWithDefault(Suit $suit): string
    {
        switch ($suit) {
            case Suit::Hearts:
            case Suit::Diamonds:
                return 'red';
            case Suit::Spades:
            case Suit::Clubs:
                return 'black';
            default:
                return 'unknown';
        }
    }

    public function onlyDefault(Suit $suit): string
    {
        switch ($suit) {
            default:
                return 'unknown';
        }
    }

    public function insideLoop(Suit $suit): string
    {
        foreach ([1, 2] as $round) {
            unset($round);

            switch ($suit) {
                case Suit::Hearts:
                    return 'red';
                default:
                    return 'other';
            }
        }

        return 'none';
    }
}
