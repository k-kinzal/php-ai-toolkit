<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\NoPrivateMethodInTestClass;

use PHPUnit\Framework\TestCase;

class WithPrivateMethod extends TestCase
{
    public function testSomething(): void
    {
        self::assertSame('hello', $this->helper());
    }

    private function helper(): string
    {
        return 'hello';
    }
}
