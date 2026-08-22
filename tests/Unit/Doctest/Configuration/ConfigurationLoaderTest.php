<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Configuration;

use PhpAiToolkit\Doctest\Configuration\Configuration;
use PhpAiToolkit\Doctest\Configuration\ConfigurationLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\ParameterCollection;

#[CoversClass(ConfigurationLoader::class)]
#[UsesClass(Configuration::class)]
final class ConfigurationLoaderTest extends TestCase
{
    public function testFromParametersResolvesPathsAgainstTheBasePath(): void
    {
        $parameters = ParameterCollection::fromArray(['directories' => 'src, lib', 'files' => 'helpers.php']);

        $config = ConfigurationLoader::fromParameters($parameters, '/app');

        self::assertSame(['/app/src', '/app/lib'], $config->getDirectories());
        self::assertSame(['/app/helpers.php'], $config->getFiles());
    }

    public function testFromParametersReadsExclusionsBootstrapAndEnabled(): void
    {
        $parameters = ParameterCollection::fromArray([
            'exclude' => '*Test.php, *Interface.php',
            'bootstrap' => 'tests/bootstrap.php',
            'enabled' => 'false',
        ]);

        $config = ConfigurationLoader::fromParameters($parameters, '/app');

        self::assertSame(['*Test.php', '*Interface.php'], $config->getExcludePatterns());
        self::assertSame('/app/tests/bootstrap.php', $config->getBootstrap());
        self::assertFalse($config->isEnabled());
    }

    public function testFromParametersDefaultsToAnEmptyEnabledConfiguration(): void
    {
        $config = ConfigurationLoader::fromParameters(ParameterCollection::fromArray([]), '/app');

        self::assertSame([], $config->getDirectories());
        self::assertSame([], $config->getFiles());
        self::assertSame([], $config->getExcludePatterns());
        self::assertNull($config->getBootstrap());
        self::assertTrue($config->isEnabled());
    }

    public function testPathsReturnsAnEmptyListForAnAbsentParameter(): void
    {
        self::assertSame([], ConfigurationLoader::paths(ParameterCollection::fromArray([]), 'directories', '/app'));
    }

    public function testPatternsTrimsAndDropsEmptyEntries(): void
    {
        $parameters = ParameterCollection::fromArray(['exclude' => ' *Test.php , , *Stub.php ']);

        self::assertSame(['*Test.php', '*Stub.php'], ConfigurationLoader::patterns($parameters, 'exclude'));
    }
}
