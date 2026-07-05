<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config;

use PhpAiToolkit\LocGuard\Config\ConfigScalarReader;
use PhpAiToolkit\LocGuard\Config\LimitConfig;
use PhpAiToolkit\LocGuard\Config\LimitConfigReader;
use PhpAiToolkit\LocGuard\LocGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LimitConfigReader::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(LimitConfig::class)]
final class LimitConfigReaderTest extends TestCase
{
    public function testReadReturnsLimitConfig(): void
    {
        $limits = (new LimitConfigReader())->read(['max_file_lines' => 10]);

        self::assertSame(10, $limits->maxFileLines);
        self::assertSame(350, $limits->maxFileNcloc);
    }

    public function testReadRejectsNonMapping(): void
    {
        $this->expectException(LocGuardException::class);

        (new LimitConfigReader())->read('strict');
    }
}
