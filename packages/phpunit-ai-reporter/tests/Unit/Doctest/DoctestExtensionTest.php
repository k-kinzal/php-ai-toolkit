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
        $parameters = ParameterCollection::fromArray(['directories' => 'src,packages/guard-php/src,packages/docgen-php/src,packages/phpstan-guard-rules/src,packages/phpunit-ai-reporter/src,packages/phpstan-ai-formatter/src']);

        (new DoctestExtension())->bootstrap(Registry::get(), PhpUnitExtensionFacade::create(), $parameters);

        $config = DoctestExtension::getConfiguration();

        self::assertNotNull($config);
        self::assertSame([dirname(__DIR__, 5) . '/src', dirname(__DIR__, 5) . '/packages/guard-php/src', dirname(__DIR__, 5) . '/packages/docgen-php/src', dirname(__DIR__, 5) . '/packages/phpstan-guard-rules/src', dirname(__DIR__, 5) . '/packages/phpunit-ai-reporter/src', dirname(__DIR__, 5) . '/packages/phpstan-ai-formatter/src'], $config->getDirectories());
    }

    public function testBootstrapKeepsADisabledConfigurationOutOfTheRun(): void
    {
        $parameters = ParameterCollection::fromArray(['directories' => 'ignored', 'enabled' => 'false']);

        (new DoctestExtension())->bootstrap(Registry::get(), PhpUnitExtensionFacade::create(), $parameters);

        $config = DoctestExtension::getConfiguration();

        self::assertNotNull($config);
        self::assertSame([dirname(__DIR__, 5) . '/src', dirname(__DIR__, 5) . '/packages/guard-php/src', dirname(__DIR__, 5) . '/packages/docgen-php/src', dirname(__DIR__, 5) . '/packages/phpstan-guard-rules/src', dirname(__DIR__, 5) . '/packages/phpunit-ai-reporter/src', dirname(__DIR__, 5) . '/packages/phpstan-ai-formatter/src'], $config->getDirectories());
    }

    public function testGetConfigurationHandsBackWhatTheRunIsWorkingFrom(): void
    {
        $config = DoctestExtension::getConfiguration();

        self::assertNotNull($config);
        self::assertSame([dirname(__DIR__, 5) . '/src', dirname(__DIR__, 5) . '/packages/guard-php/src', dirname(__DIR__, 5) . '/packages/docgen-php/src', dirname(__DIR__, 5) . '/packages/phpstan-guard-rules/src', dirname(__DIR__, 5) . '/packages/phpunit-ai-reporter/src', dirname(__DIR__, 5) . '/packages/phpstan-ai-formatter/src'], $config->getDirectories());
    }

    public function testDeclaredConfigurationReadsTheParametersPhpUnitXmlCarries(): void
    {
        $config = DoctestExtension::declaredConfiguration(Registry::get());

        self::assertNotNull($config);
        self::assertSame([dirname(__DIR__, 5) . '/src', dirname(__DIR__, 5) . '/packages/guard-php/src', dirname(__DIR__, 5) . '/packages/docgen-php/src', dirname(__DIR__, 5) . '/packages/phpstan-guard-rules/src', dirname(__DIR__, 5) . '/packages/phpunit-ai-reporter/src', dirname(__DIR__, 5) . '/packages/phpstan-ai-formatter/src'], $config->getDirectories());
    }

    public function testBasePathIsTheDirectoryHoldingThePhpUnitConfiguration(): void
    {
        $basePath = DoctestExtension::basePath(Registry::get());

        self::assertSame(dirname(__DIR__, 5), $basePath);
    }
}
