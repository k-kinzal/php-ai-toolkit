<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireExhaustiveDispatch;

use LogicException;

final class ShapeDispatch
{
    public function partialMatch(Circle|Square|Triangle $shape): string
    {
        return match (true) {
            $shape instanceof Circle => 'circle',
            $shape instanceof Square => 'square',
            default => throw new LogicException('unreachable'),
        };
    }

    public function completeMatch(Circle|Square|Triangle $shape): string
    {
        return match (true) {
            $shape instanceof Circle => 'circle',
            $shape instanceof Square => 'square',
            $shape instanceof Triangle => 'triangle',
            default => throw new LogicException('unreachable'),
        };
    }

    public function partialSwitch(Circle|Square|Triangle $shape): string
    {
        switch (true) {
            case $shape instanceof Circle:
                return 'circle';
            case $shape instanceof Square:
                return 'square';
        }

        return 'unknown';
    }

    public function openHierarchySwitch(Shape $shape): string
    {
        switch (true) {
            case $shape instanceof Circle:
                return 'circle';
            default:
                return 'other';
        }
    }

    public function classStringMatch(Circle|Square|Triangle $shape): string
    {
        return match ($shape::class) {
            Circle::class => 'circle',
            Square::class => 'square',
            default => 'other',
        };
    }

    public function getClassSwitch(Circle|Square|Triangle $shape): string
    {
        switch (get_class($shape)) {
            case Circle::class:
                return 'circle';
            default:
                return 'other';
        }
    }

    public function mixedConditionMatch(Circle|Square|Triangle $shape, int $size): string
    {
        return match (true) {
            $shape instanceof Circle => 'circle',
            $size > 10 => 'big',
            default => 'other',
        };
    }
}
