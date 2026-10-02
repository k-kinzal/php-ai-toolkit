<?php

declare(strict_types=1);

namespace Tests\Fixture\PhpUnitMockApi;

use PHPUnit\Framework\TestCase;

class NonLiteral extends TestCase
{
    public function testMockWithVariable(): void
    {
        $className = DependencyInterface::class;
        $this->createMock($className);
    }
}
