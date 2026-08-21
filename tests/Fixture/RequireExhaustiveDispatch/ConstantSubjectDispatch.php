<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireExhaustiveDispatch;

final class ConstantSubjectDispatch
{
    public const MODE_FAST = 'fast';

    public const MODE_SAFE = 'safe';

    public const MODE_DRY = 'dry';

    /**
     * @param self::MODE_* $mode
     */
    public function partialMatch(string $mode): string
    {
        return match ($mode) {
            self::MODE_FAST => 'f',
            default => 'x',
        };
    }

    /**
     * @param self::MODE_* $mode
     */
    public function completeMatch(string $mode): string
    {
        return match ($mode) {
            self::MODE_FAST => 'f',
            self::MODE_SAFE => 's',
            self::MODE_DRY => 'd',
            default => 'x',
        };
    }

    public function boolMatch(bool $flag): string
    {
        return match ($flag) {
            true => 'yes',
            default => 'no',
        };
    }

    /**
     * @param 1|2|3 $level
     */
    public function integerUnionMatch(int $level): string
    {
        return match ($level) {
            1 => 'low',
            default => 'high',
        };
    }

    public function backedEnumValueMatch(Suit $suit): string
    {
        return match ($suit->value) {
            'hearts' => 'red',
            default => 'other',
        };
    }
}
