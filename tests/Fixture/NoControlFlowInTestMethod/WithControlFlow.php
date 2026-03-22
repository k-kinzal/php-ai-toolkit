<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\NoControlFlowInTestMethod;

use PHPUnit\Framework\TestCase;

class WithControlFlow extends TestCase
{
    public function testWithIf(): void
    {
        $x = 1;
        if ($x === 1) {
            self::assertTrue(true);
        }
    }

    public function testWithForeach(): void
    {
        foreach ([1, 2, 3] as $item) {
            self::assertIsInt($item);
        }
    }
}
