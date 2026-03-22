<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\NoHelperMethodInTestClass;

use Override;
use PHPUnit\Framework\TestCase;

class CleanTestClass extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testSomething(): void
    {
        self::assertTrue(true);
    }

    public static function providerData(): array
    {
        return [['a'], ['b']];
    }
}
