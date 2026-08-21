<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Config;

use PhpAiToolkit\Doctest\Config\ConfigLoader;
use PhpAiToolkit\Doctest\Config\ConfigScalarReader;
use PhpAiToolkit\Doctest\Config\ConfigStringListReader;
use PhpAiToolkit\Doctest\Config\DoctestConfig;
use PhpAiToolkit\Doctest\Config\ReportConfig;
use PhpAiToolkit\Doctest\Config\ReportConfigReader;
use PhpAiToolkit\Doctest\DoctestException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigLoader::class)]
#[UsesClass(DoctestConfig::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ReportConfigReader::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
final class ConfigLoaderTest extends TestCase
{
    public function testLoadReadsTheFixtureConfiguration(): void
    {
        $config = (new ConfigLoader())->load(__DIR__ . '/../../../Fixture/Doctest/project/doctest.yaml');

        self::assertSame(['src'], $config->paths);
        self::assertSame(['src/Nested/*'], $config->exclude);
        self::assertNull($config->bootstrap);
        self::assertSame('ai', $config->report->reporter);
        self::assertStringEndsWith('Fixture/Doctest/project', $config->root);
    }

    public function testLoadRejectsAMissingFile(): void
    {
        $this->expectException(DoctestException::class);
        $this->expectExceptionMessage('Doctest config not found');

        (new ConfigLoader())->load(__DIR__ . '/missing.yaml');
    }

    public function testLoadRejectsAFileThatIsNotAMapping(): void
    {
        $path = sys_get_temp_dir() . '/doctest-config-loader-scalar.yaml';
        file_put_contents($path, "just a string\n");

        $this->expectException(DoctestException::class);
        $this->expectExceptionMessage('top-level value must be a mapping');

        (new ConfigLoader())->load($path);
    }
}
