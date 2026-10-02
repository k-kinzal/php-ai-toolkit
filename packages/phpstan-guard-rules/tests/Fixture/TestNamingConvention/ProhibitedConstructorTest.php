<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\TestNamingConvention;

use PHPUnit\Framework\TestCase;

class ProhibitedConstructorTest extends TestCase
{
    public function testConstruct(): void
    {
        self::assertTrue(true);
    }

    public function testConstructor(): void
    {
        self::assertTrue(true);
    }

    public function testConstructThrowsException(): void
    {
        self::assertTrue(true);
    }

    public function testDestruct(): void
    {
        self::assertTrue(true);
    }

    public function testDestructor(): void
    {
        self::assertTrue(true);
    }

    public function testDestructorIsCalled(): void
    {
        self::assertTrue(true);
    }
}
