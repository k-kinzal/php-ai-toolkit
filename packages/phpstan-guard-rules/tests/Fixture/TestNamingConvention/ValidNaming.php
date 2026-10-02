<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\TestNamingConvention;

use PHPUnit\Framework\TestCase;

class ValidNaming extends TestCase
{
    public function testSomething(): void
    {
        self::assertTrue(true);
    }

    public function testUserCanLogin(): void
    {
        self::assertTrue(true);
    }

    public function testReconstructData(): void
    {
        self::assertTrue(true);
    }

    public static function providerData(): array
    {
        return [['a'], ['b']];
    }

    public static function providerValidEmails(): array
    {
        return [['alice@example.com'], ['bob@example.com']];
    }
}
