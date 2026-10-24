<?php

declare(strict_types=1);

namespace Tests\Property\Fixture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RestrictionsProperties extends TestCase
{
    public const VALUE = 1;
    public string $value = '';
    private function helper(): void {}
    #[Test]
    public function satisfiesBehavior(): void
    {
        if (rand(0, 1)) {
            self::assertTrue(true);
        }
        new \ReflectionClass(self::class);
        $this->createMock(\stdClass::class);
    }
}
