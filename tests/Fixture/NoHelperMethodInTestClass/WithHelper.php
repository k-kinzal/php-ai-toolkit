<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\NoHelperMethodInTestClass;

use PHPUnit\Framework\TestCase;

class WithHelper extends TestCase
{
    public function testSomething(): void
    {
        self::assertTrue(true);
    }

    protected function buildUser(): string
    {
        return 'Alice';
    }
}
