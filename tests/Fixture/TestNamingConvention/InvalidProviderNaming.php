<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\TestNamingConvention;

use PHPUnit\Framework\TestCase;

class InvalidProviderNaming extends TestCase
{
    public function testSomething(): void
    {
        self::assertTrue(true);
    }

    public static function provider(): array
    {
        return [['a']];
    }

    public static function providerdata(): array
    {
        return [['a']];
    }

    public static function provider_data(): array
    {
        return [['a']];
    }
}
