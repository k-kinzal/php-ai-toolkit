<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\NoClassConstantInTestClass;

use PHPUnit\Framework\TestCase;

class WithConstant extends TestCase
{
    private const FOO = 'bar';

    public function testSomething(): void
    {
        self::assertSame('bar', self::FOO);
    }
}
