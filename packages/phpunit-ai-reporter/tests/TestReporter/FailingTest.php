<?php

declare(strict_types=1);

namespace Tests\TestReporter;

use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FailingTest extends TestCase
{
    public function testFails(): void
    {
        self::assertSame('expected', 'actual');
    }

    public function testErrors(): void
    {
        throw new RuntimeException('fixture error');
    }

    public function testIsRisky(): void
    {
    }
}
