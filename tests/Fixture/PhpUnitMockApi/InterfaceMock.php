<?php

declare(strict_types=1);

namespace Tests\Fixture\PhpUnitMockApi;

use PHPUnit\Framework\TestCase;

interface DependencyInterface
{
    public function fetch(): string;
}

class InterfaceMock extends TestCase
{
    public function testMockInterface(): void
    {
        $this->createMock(DependencyInterface::class);
    }

    public function testStubInterface(): void
    {
        $this->createStub(DependencyInterface::class);
    }
}
