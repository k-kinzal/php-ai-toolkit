<?php

declare(strict_types=1);

namespace Tests\PhpStan\PhpUnitMockApi;

use PHPUnit\Framework\TestCase;

class ConcreteService
{
    public function run(): void
    {
    }
}

class ConcreteClassMock extends TestCase
{
    public function testMockConcreteClass(): void
    {
        $this->createMock(ConcreteService::class);
    }
}
