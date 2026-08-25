<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Config\DocGenConfig;

/**
 * @covers \Toolkit\DocGen\Config\DocGenConfig
 */
#[CoversClass(DocGenConfig::class)]
final class DocGenConfigTest extends TestCase
{
    public function testStoresConfigurationValues(): void
    {
        $config = new DocGenConfig(
            '/project',
            ['.', 'packages/*'],
            ['vendor-docs'],
            ['src/Generated'],
            'public/docs',
            'My Project',
            'deptrac.yaml',
            'build/coverage.xml',
            ['phpunit/*'],
            'build/doc-gen-cache',
            'https://example.github.io/project',
            'https://github.com/example/project',
        );

        self::assertSame('/project', $config->root);
        self::assertSame(['.', 'packages/*'], $config->packages);
        self::assertSame(['vendor-docs'], $config->vendor);
        self::assertSame(['phpunit/*'], $config->vendorDev);
        self::assertSame(['src/Generated'], $config->exclude);
        self::assertSame('public/docs', $config->output);
        self::assertSame('My Project', $config->title);
        self::assertSame('deptrac.yaml', $config->deptrac);
        self::assertSame('build/coverage.xml', $config->coverage);
        self::assertSame('build/doc-gen-cache', $config->cache);
        self::assertSame('https://example.github.io/project', $config->baseUrl);
        self::assertSame('https://github.com/example/project', $config->repository);
    }

    public function testStoresNullsForAbsentOptionalValues(): void
    {
        $config = new DocGenConfig('/project', [], [], [], 'build/docs', null, null, null);

        self::assertSame([], $config->packages);
        self::assertSame([], $config->vendor);
        self::assertSame([], $config->vendorDev);
        self::assertSame([], $config->exclude);
        self::assertNull($config->title);
        self::assertNull($config->deptrac);
        self::assertNull($config->coverage);
        self::assertNull($config->baseUrl);
        self::assertNull($config->repository);
    }
}
