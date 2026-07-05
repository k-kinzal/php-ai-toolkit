<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config;

use PhpAiToolkit\LocGuard\Config\ConfigLoader;
use PhpAiToolkit\LocGuard\Config\ConfigScalarReader;
use PhpAiToolkit\LocGuard\Config\ConfigStringListReader;
use PhpAiToolkit\LocGuard\Config\LimitConfig;
use PhpAiToolkit\LocGuard\Config\LimitConfigReader;
use PhpAiToolkit\LocGuard\Config\LocGuardConfig;
use PhpAiToolkit\LocGuard\Config\ReportConfig;
use PhpAiToolkit\LocGuard\Config\ReportConfigReader;
use PhpAiToolkit\LocGuard\LocGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigLoader::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(LimitConfigReader::class)]
#[UsesClass(LocGuardConfig::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ReportConfigReader::class)]
final class ConfigLoaderTest extends TestCase
{
    public function testLoadsLocYaml(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-config-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/loc.yaml', <<<'YAML'
paths:
  - src
exclude:
  - 'src/Generated/*'
limits:
  max_file_lines: 10
  max_file_ncloc: 7
  max_class_lines: 11
  max_trait_lines: 12
  max_interface_lines: 13
  max_enum_lines: 14
  max_function_lines: 8
  max_method_lines: 9
  max_cyclomatic_complexity: 4
report:
  reporter: json
  order_by:
    - rule
    - path
YAML);

        $config = (new ConfigLoader())->load($dir . '/loc.yaml');

        self::assertSame($dir, $config->root);
        self::assertSame(['src'], $config->paths);
        self::assertSame(['src/Generated/*'], $config->exclude);
        self::assertSame(10, $config->limits->maxFileLines);
        self::assertSame(7, $config->limits->maxFileNcloc);
        self::assertSame(11, $config->limits->maxClassLines);
        self::assertSame(12, $config->limits->maxTraitLines);
        self::assertSame(13, $config->limits->maxInterfaceLines);
        self::assertSame(14, $config->limits->maxEnumLines);
        self::assertSame(8, $config->limits->maxFunctionLines);
        self::assertSame(9, $config->limits->maxMethodLines);
        self::assertSame(4, $config->limits->maxCyclomaticComplexity);
        self::assertSame('json', $config->report->reporter);
        self::assertSame(['rule', 'path'], $config->report->orderBy);
    }

    public function testLoadRejectsMissingConfig(): void
    {
        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('config not found');

        (new ConfigLoader())->load(sys_get_temp_dir() . '/missing-locguard-' . bin2hex(random_bytes(4)) . '.yaml');
    }

    public function testLoadRejectsMalformedYaml(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-config-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/loc.yaml', "paths: [\n");

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('Invalid loc.yaml');

        (new ConfigLoader())->load($dir . '/loc.yaml');
    }

    public function testLoadRejectsScalarTopLevelYaml(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-config-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/loc.yaml', "42\n");

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('top-level value must be a mapping');

        (new ConfigLoader())->load($dir . '/loc.yaml');
    }

    public function testLoadRejectsInvalidPathList(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-config-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/loc.yaml', <<<'YAML'
paths: src
YAML);

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('"paths" must be a list of strings');

        (new ConfigLoader())->load($dir . '/loc.yaml');
    }

    public function testLoadRejectsInvalidExcludeEntry(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-config-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/loc.yaml', <<<'YAML'
exclude:
  - 1
YAML);

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('"exclude" must be a list of strings');

        (new ConfigLoader())->load($dir . '/loc.yaml');
    }

    public function testRejectsInvalidLimit(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-config-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/loc.yaml', <<<'YAML'
limits:
  max_method_lines: 0
YAML);

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('limits.max_method_lines');

        (new ConfigLoader())->load($dir . '/loc.yaml');
    }

    public function testRejectsInvalidLimitsMapping(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-config-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/loc.yaml', <<<'YAML'
limits: strict
YAML);

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('"limits" must be a mapping');

        (new ConfigLoader())->load($dir . '/loc.yaml');
    }

    public function testRejectsInvalidReporter(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-config-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/loc.yaml', <<<'YAML'
report:
  reporter: xml
YAML);

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('report.reporter');

        (new ConfigLoader())->load($dir . '/loc.yaml');
    }

    public function testRejectsInvalidReportMapping(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-config-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/loc.yaml', <<<'YAML'
report: text
YAML);

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('"report" must be a mapping');

        (new ConfigLoader())->load($dir . '/loc.yaml');
    }

    public function testRejectsEmptyReporterName(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-config-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/loc.yaml', <<<'YAML'
report:
  reporter: ''
YAML);

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('"reporter" must be a non-empty string');

        (new ConfigLoader())->load($dir . '/loc.yaml');
    }

    public function testRejectsInvalidReportOrderField(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-config-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/loc.yaml', <<<'YAML'
report:
  order_by:
    - severity
YAML);

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('report.order_by');

        (new ConfigLoader())->load($dir . '/loc.yaml');
    }
}
