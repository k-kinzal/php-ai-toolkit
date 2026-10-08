<?php

declare(strict_types=1);

namespace App\Fixture\NoControlFlowInTestMethod;

class NonTestClass
{
    public function testWithIf(): void
    {
        if (true) {
            return;
        }
    }
}
