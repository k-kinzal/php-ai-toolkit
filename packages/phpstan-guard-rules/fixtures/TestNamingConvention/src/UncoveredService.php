<?php

declare(strict_types=1);

namespace Fixture\TestNamingConvention\Src;

class UncoveredService
{
    public function execute(): void
    {
    }

    public function getResult(): string
    {
        return '';
    }

    public function __toString(): string
    {
        return '';
    }

    private function helper(): void
    {
    }
}
