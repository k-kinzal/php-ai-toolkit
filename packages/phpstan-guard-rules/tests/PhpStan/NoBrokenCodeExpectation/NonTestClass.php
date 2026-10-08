<?php

declare(strict_types=1);

namespace Example\Fixture\NoBrokenCodeExpectation;

use LogicException;
use PHPUnit\Framework\TestCase;

final class NonTestClass extends TestCase
{
    public function testNamespaceControlsRuleScope(): void
    {
        $this->expectException(LogicException::class);
    }
}
