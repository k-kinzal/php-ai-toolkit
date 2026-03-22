<?php

declare(strict_types=1);

namespace Fixture\TestNamingConvention\Src;

class CoveredService
{
    public function process(): bool
    {
        return true;
    }

    public function getName(): string
    {
        return 'name';
    }

    public function __construct()
    {
    }

    private function helper(): void
    {
    }

    protected function internal(): void
    {
    }
}
