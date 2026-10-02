<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Cli\DocGenCliArgumentParser;
use Toolkit\DocGen\Cli\DocGenConfigFactory;
use Toolkit\DocGen\Config\BaseUrl;
use Toolkit\DocGen\Config\DocGenConfig;
use Toolkit\DocGen\Config\RepositoryUrl;

/**
 * @covers \Toolkit\DocGen\Cli\DocGenConfigFactory
 * @uses \Toolkit\DocGen\Config\BaseUrl
 * @uses \Toolkit\DocGen\Cli\DocGenCliArgumentParser
 * @uses \Toolkit\DocGen\Config\DocGenConfig
 * @uses \Toolkit\DocGen\Config\RepositoryUrl
 */
#[CoversClass(DocGenConfigFactory::class)]
#[UsesClass(BaseUrl::class)]
#[UsesClass(DocGenCliArgumentParser::class)]
#[UsesClass(DocGenConfig::class)]
#[UsesClass(RepositoryUrl::class)]
final class DocGenConfigFactoryTest extends TestCase
{
    public function testCreateReadsEveryOptionOfTheRun(): void
    {
        $arguments = (new DocGenCliArgumentParser())->parse([
            '--packages=.,packages/*',
            '--vendor=acme/*',
            '--vendor-dev=phpunit/*',
            '--exclude=tests/Fixture/*',
            '--output=public/site',
            '--title=Demo Docs',
            '--deptrac=conf/deptrac.yaml',
            '--coverage=build/coverage-xml',
            '--cache-dir=.docgen',
            '--base-url=https://example.github.io/project',
            '--repository=https://github.com/example/project',
            '--public-api',
        ]);

        $config = (new DocGenConfigFactory())->create(sys_get_temp_dir(), $arguments);

        self::assertSame(realpath(sys_get_temp_dir()), $config->root);
        self::assertSame(['.', 'packages/*'], $config->packages);
        self::assertSame(['acme/*'], $config->vendor);
        self::assertSame(['phpunit/*'], $config->vendorDev);
        self::assertSame(['tests/Fixture/*'], $config->exclude);
        self::assertSame('public/site', $config->output);
        self::assertSame('Demo Docs', $config->title);
        self::assertSame('conf/deptrac.yaml', $config->deptrac);
        self::assertSame('build/coverage-xml', $config->coverage);
        self::assertSame('.docgen', $config->cache);
        self::assertSame('https://example.github.io/project', $config->baseUrl);
        self::assertSame('https://github.com/example/project', $config->repository);
        self::assertTrue($config->publicApi);
    }

    public function testCreateFallsBackToTheDefaultsOfARunWithoutOptions(): void
    {
        $config = (new DocGenConfigFactory())->create(sys_get_temp_dir(), (new DocGenCliArgumentParser())->parse([]));

        self::assertSame(['.', 'packages/*'], $config->packages);
        self::assertSame([], $config->vendor);
        self::assertSame([], $config->vendorDev);
        self::assertSame([], $config->exclude);
        self::assertSame('build/docs', $config->output);
        self::assertSame('build/docgen-cache', $config->cache);
        self::assertNull($config->title);
        self::assertNull($config->deptrac);
        self::assertNull($config->coverage);
        self::assertNull($config->baseUrl);
        self::assertNull($config->repository);
        self::assertFalse($config->publicApi);
    }

    public function testCreateKeepsTheWorkingDirectoryThatCannotBeResolved(): void
    {
        $config = (new DocGenConfigFactory())->create('/no/such/project', (new DocGenCliArgumentParser())->parse([]));

        self::assertSame('/no/such/project', $config->root);
    }

    public function testCacheIsTheDirectoryOfTheRunUnlessItCachesNothing(): void
    {
        $parser = new DocGenCliArgumentParser();
        $factory = new DocGenConfigFactory();

        self::assertSame('build/docgen-cache', $factory->cache($parser->parse([])));
        self::assertSame('.docgen', $factory->cache($parser->parse(['--cache-dir=.docgen'])));
        self::assertNull($factory->cache($parser->parse(['--no-cache'])));
        self::assertNull($factory->cache($parser->parse(['--no-cache', '--cache-dir=.docgen'])));
    }

    public function testCacheDirectoryNamesWhatARunClearsEvenWhenItCachesNothing(): void
    {
        $parser = new DocGenCliArgumentParser();
        $factory = new DocGenConfigFactory();

        self::assertSame('build/docgen-cache', $factory->cacheDirectory($parser->parse([])));
        self::assertSame('.docgen', $factory->cacheDirectory($parser->parse(['--no-cache', '--cache-dir=.docgen'])));
    }

    public function testCreateTurnsTheCacheOffForARunThatAskedFor(): void
    {
        $config = (new DocGenConfigFactory())->create(sys_get_temp_dir(), (new DocGenCliArgumentParser())->parse(['--no-cache']));

        self::assertNull($config->cache);
    }
}
