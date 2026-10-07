<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbiddenMagicMethodCall;

class Stringable
{
    public function __toString(): string
    {
        return '';
    }
}

class DirectCall
{
    public function run(Stringable $obj): void
    {
        $obj->__toString();
    }
}
