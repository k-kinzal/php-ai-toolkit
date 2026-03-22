<?php

declare(strict_types=1);

namespace Tests\Fixture\PhpUnitMockApi;

use PHPUnit\Framework\TestCase;

interface SomeInterface
{
    public function execute(): void;
}

class ProhibitedApi extends TestCase
{
    public function testWithGetMockBuilder(): void
    {
        $this->getMockBuilder(SomeInterface::class)->getMock();
    }

    public function testWithCreatePartialMock(): void
    {
        $this->createPartialMock(SomeInterface::class, ['execute']);
    }
}
