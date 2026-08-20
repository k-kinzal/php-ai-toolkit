<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireExhaustiveDispatch;

final class OpenSubjectDispatch
{
    public function stringMatch(string $name): ?string
    {
        return match ($name) {
            'a' => 'A',
            'b' => 'B',
            default => null,
        };
    }

    public function integerSwitch(int $count): string
    {
        switch ($count) {
            case 1:
                return 'one';
            case 2:
                return 'two';
            default:
                return 'many';
        }
    }

    public function objectIdentityMatch(Circle|Square|Triangle $shape, Circle $known): string
    {
        return match ($shape) {
            $known => 'known',
            default => 'other',
        };
    }
}
