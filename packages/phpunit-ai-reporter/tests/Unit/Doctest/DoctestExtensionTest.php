<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Registry;
use Tests\Fixture\Doctest\PhpUnitExtensionFacade;
use Toolkit\Doctest\Configuration\Configuration;
use Toolkit\Doctest\Configuration\ConfigurationLoader;
use Toolkit\Doctest\DoctestExtension;

/**
 * @covers \Toolkit\Doctest\DoctestExtension
 * @uses \Toolkit\Doctest\Configuration\Configuration
 * @uses \Toolkit\Doctest\Configuration\ConfigurationLoader
 */
#[CoversClass(DoctestExtension::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationLoader::class)]
final class DoctestExtensionTest extends TestCase
{
    public function testBootstrapStoresTheConfigurationReadFromTheParameters(): void
    {
        $configuration = Registry::get();
        $parameters = ParameterCollection::fromArray(['directories' => 'src']);

        (new DoctestExtension())->bootstrap($configuration, PhpUnitExtensionFacade::create(), $parameters);

        $config = DoctestExtension::getConfiguration();

        self::assertNotNull($config);
        self::assertSame([DoctestExtension::basePath($configuration) . '/src'], $config->getDirectories());
    }

    public function testBootstrapKeepsADisabledConfigurationOutOfTheRun(): void
    {
        $configuration = Registry::get();
        (new DoctestExtension())->bootstrap(
            $configuration,
            PhpUnitExtensionFacade::create(),
            ParameterCollection::fromArray(['directories' => 'src']),
        );
        $before = DoctestExtension::getConfiguration();

        (new DoctestExtension())->bootstrap(
            $configuration,
            PhpUnitExtensionFacade::create(),
            ParameterCollection::fromArray(['directories' => 'ignored', 'enabled' => 'false']),
        );

        self::assertNotNull($before);
        self::assertSame($before->getDirectories(), DoctestExtension::getConfiguration()?->getDirectories());
    }

    public function testGetConfigurationHandsBackWhatTheRunIsWorkingFrom(): void
    {
        $configuration = Registry::get();
        (new DoctestExtension())->bootstrap(
            $configuration,
            PhpUnitExtensionFacade::create(),
            ParameterCollection::fromArray(['directories' => 'src']),
        );

        $config = DoctestExtension::getConfiguration();

        self::assertNotNull($config);
        self::assertSame([DoctestExtension::basePath($configuration) . '/src'], $config->getDirectories());
    }

    public function testDeclaredConfigurationReadsTheParametersPhpUnitXmlCarries(): void
    {
        $configuration = Registry::get();
        $config = DoctestExtension::declaredConfiguration($configuration);

        self::assertNotNull($config);
        self::assertNotSame([], $config->getDirectories());
        self::assertStringStartsWith(DoctestExtension::basePath($configuration) . '/', $config->getDirectories()[0]);
    }

    public function testBasePathIsTheDirectoryHoldingThePhpUnitConfiguration(): void
    {
        $configuration = Registry::get();

        self::assertTrue($configuration->hasConfigurationFile());
        self::assertSame(dirname($configuration->configurationFile()), DoctestExtension::basePath($configuration));
    }
}
