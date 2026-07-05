<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config;

use PhpAiToolkit\LocGuard\Config\ConfigStringListReader;
use PhpAiToolkit\LocGuard\LocGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigStringListReader::class)]
final class ConfigStringListReaderTest extends TestCase
{
    public function testReadReturnsConfiguredStringList(): void
    {
        self::assertSame(['src'], (new ConfigStringListReader())->read(['paths' => ['src']], 'paths', []));
    }

    public function testReadRejectsInvalidStringList(): void
    {
        $this->expectException(LocGuardException::class);

        (new ConfigStringListReader())->read(['paths' => [1]], 'paths', []);
    }
}
