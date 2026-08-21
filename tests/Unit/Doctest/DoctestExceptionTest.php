<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest;

use PhpAiToolkit\Doctest\DoctestException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(DoctestException::class)]
final class DoctestExceptionTest extends TestCase
{
    /**
     * @throws DoctestException
     */
    public function testIsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('broken config');

        throw new DoctestException('broken config');
    }
}
