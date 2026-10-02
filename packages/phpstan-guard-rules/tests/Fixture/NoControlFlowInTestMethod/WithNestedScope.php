<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\NoControlFlowInTestMethod;

use PHPUnit\Framework\TestCase;

class WithNestedScope extends TestCase
{
    public function testWithClosure(): void
    {
        $fn = static function (): bool {
            if (true) {
                return true;
            }

            return false;
        };
        self::assertTrue($fn());
    }
}
