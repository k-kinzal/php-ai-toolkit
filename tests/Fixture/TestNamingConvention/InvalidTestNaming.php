<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\TestNamingConvention;

use PHPUnit\Framework\TestCase;

class InvalidTestNaming extends TestCase
{
    public function test(): void
    {
        self::assertTrue(true);
    }

    public function testsomething(): void
    {
        self::assertTrue(true);
    }

    public function test_something(): void
    {
        self::assertTrue(true);
    }
}
