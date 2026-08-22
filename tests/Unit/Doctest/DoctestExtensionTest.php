<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest;

use PhpAiToolkit\Doctest\Configuration\Configuration;
use PhpAiToolkit\Doctest\Configuration\ConfigurationLoader;
use PhpAiToolkit\Doctest\DoctestExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\ExtensionFacade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Registry;

#[CoversClass(DoctestExtension::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationLoader::class)]
final class DoctestExtensionTest extends TestCase
{
    public function testGetConfigurationReturnsWhatTheRunningExtensionRead(): void
    {
        $config = DoctestExtension::getConfiguration();

        self::assertNotNull($config);
        self::assertStringEndsWith('/src', $config->getDirectories()[0]);
    }

    public function testBootstrapStoresTheConfigurationReadFromTheParameters(): void
    {
        $parameters = ParameterCollection::fromArray(['directories' => 'src']);

        (new DoctestExtension())->bootstrap(Registry::get(), new ExtensionFacade(), $parameters);

        $config = DoctestExtension::getConfiguration();

        self::assertNotNull($config);
        self::assertSame([dirname(__DIR__, 3) . '/src'], $config->getDirectories());
    }

    public function testBootstrapKeepsADisabledConfigurationOutOfTheRun(): void
    {
        $parameters = ParameterCollection::fromArray(['directories' => 'ignored', 'enabled' => 'false']);

        (new DoctestExtension())->bootstrap(Registry::get(), new ExtensionFacade(), $parameters);

        $config = DoctestExtension::getConfiguration();

        self::assertNotNull($config);
        self::assertSame([dirname(__DIR__, 3) . '/src'], $config->getDirectories());
    }

    public function testBasePathIsTheDirectoryHoldingThePhpUnitConfiguration(): void
    {
        $basePath = (new DoctestExtension())->basePath(Registry::get());

        self::assertSame(dirname(__DIR__, 3), $basePath);
    }
}
