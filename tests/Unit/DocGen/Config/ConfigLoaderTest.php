<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Config;

use PhpAiToolkit\DocGen\Config\BaseUrl;
use PhpAiToolkit\DocGen\Config\ConfigLoader;
use PhpAiToolkit\DocGen\Config\ConfigScalarReader;
use PhpAiToolkit\DocGen\Config\ConfigStringListReader;
use PhpAiToolkit\DocGen\Config\DocGenConfig;
use PhpAiToolkit\DocGen\Config\RepositoryUrl;
use PhpAiToolkit\DocGen\DocGenException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigLoader::class)]
#[UsesClass(BaseUrl::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(DocGenConfig::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(RepositoryUrl::class)]
final class ConfigLoaderTest extends TestCase
{
    public function testLoadAppliesDefaultsForEmptyMapping(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-config-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/doc.yaml', '{}');

        $config = (new ConfigLoader())->load($dir . '/doc.yaml');

        self::assertSame(realpath($dir), $config->root);
        self::assertSame(['.', 'packages/*'], $config->packages);
        self::assertSame([], $config->vendor);
        self::assertSame([], $config->vendorDev);
        self::assertSame([], $config->exclude);
        self::assertSame('build/docs', $config->output);
        self::assertNull($config->title);
        self::assertNull($config->deptrac);
        self::assertNull($config->coverage);
        self::assertNull($config->baseUrl);
        self::assertNull($config->repository);
    }

    public function testLoadRoundTripsAllKeys(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-config-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/doc.yaml', <<<'YAML'
packages:
  - src
  - packages/*
vendor:
  - vendor-docs
vendor_dev:
  - phpunit/*
exclude:
  - src/Generated
output: public/docs
title: My Project
deptrac: deptrac.yaml
coverage: build/coverage.xml
base_url: https://example.github.io/project/
repository: https://github.com/example/project/
YAML);

        $config = (new ConfigLoader())->load($dir . '/doc.yaml');

        self::assertSame(realpath($dir), $config->root);
        self::assertSame(['src', 'packages/*'], $config->packages);
        self::assertSame(['vendor-docs'], $config->vendor);
        self::assertSame(['phpunit/*'], $config->vendorDev);
        self::assertSame(['src/Generated'], $config->exclude);
        self::assertSame('public/docs', $config->output);
        self::assertSame('My Project', $config->title);
        self::assertSame('deptrac.yaml', $config->deptrac);
        self::assertSame('build/coverage.xml', $config->coverage);
        self::assertSame('https://example.github.io/project', $config->baseUrl);
        self::assertSame('https://github.com/example/project', $config->repository);
    }

    public function testLoadRejectsARepositoryNoPageCanLinkTo(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-config-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/doc.yaml', 'repository: git@github.com:example/project.git');

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid repository: git@github.com:example/project.git.');

        (new ConfigLoader())->load($dir . '/doc.yaml');
    }

    public function testLoadRejectsUnknownTopLevelKey(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-config-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/doc.yaml', <<<'YAML'
bogus: 1
YAML);

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid doc.yaml: top-level contains unsupported key "bogus".');

        (new ConfigLoader())->load($dir . '/doc.yaml');
    }

    public function testLoadRejectsMissingFile(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-config-' . uniqid('', true);
        mkdir($dir, 0777, true);

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('DocGen config not found: ' . $dir . '/doc.yaml');

        (new ConfigLoader())->load($dir . '/doc.yaml');
    }

    public function testLoadRejectsMalformedYaml(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-config-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/doc.yaml', <<<'YAML'
title: "unclosed
YAML);

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid doc.yaml: Malformed inline YAML string');

        (new ConfigLoader())->load($dir . '/doc.yaml');
    }

    public function testLoadRejectsScalarTopLevel(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-config-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/doc.yaml', <<<'YAML'
just a string
YAML);

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid doc.yaml: top-level value must be a mapping.');

        (new ConfigLoader())->load($dir . '/doc.yaml');
    }
}
