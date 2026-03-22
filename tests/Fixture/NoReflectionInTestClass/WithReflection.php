<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\NoReflectionInTestClass;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

class WithReflection extends TestCase
{
    public function testAccessPrivateProperty(): void
    {
        $object = new \stdClass();
        $reflection = new ReflectionClass($object);
        self::assertSame('stdClass', $reflection->getName());
    }

    public function testAccessPrivateMethod(): void
    {
        $reflection = new \ReflectionMethod(self::class, 'helper');
        $reflection->setAccessible(true);
        self::assertTrue($reflection->isPrivate());
    }

    private function helper(): void
    {
    }
}
