<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config;

use PhpAiToolkit\LocGuard\Config\ConfigScalarReader;
use PhpAiToolkit\LocGuard\LocGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigScalarReader::class)]
final class ConfigScalarReaderTest extends TestCase
{
    public function testStringReturnsNonEmptyString(): void
    {
        self::assertSame('ai', (new ConfigScalarReader())->string(['reporter' => 'ai'], 'reporter', 'text'));
    }

    public function testPositiveIntReturnsPositiveInteger(): void
    {
        self::assertSame(10, (new ConfigScalarReader())->positiveInt(['max_file_lines' => 10], 'max_file_lines', 500));
    }

    public function testStringRejectsEmptyString(): void
    {
        $this->expectException(LocGuardException::class);

        (new ConfigScalarReader())->string(['reporter' => ''], 'reporter', 'ai');
    }

    public function testPositiveIntRejectsNonPositiveInteger(): void
    {
        $this->expectException(LocGuardException::class);

        (new ConfigScalarReader())->positiveInt(['max_file_lines' => 0], 'max_file_lines', 500);
    }
}
