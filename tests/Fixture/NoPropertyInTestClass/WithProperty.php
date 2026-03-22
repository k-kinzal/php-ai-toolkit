<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\NoPropertyInTestClass;

use PHPUnit\Framework\TestCase;

class WithProperty extends TestCase
{
    private string $name = 'Alice';

    public function testSomething(): void
    {
        self::assertSame('Alice', $this->name);
    }
}
