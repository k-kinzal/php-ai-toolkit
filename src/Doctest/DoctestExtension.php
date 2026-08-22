<?php

declare(strict_types=1);

/**
 * PHPUnit extension entry point for doctest.
 *
 * @example File-level example: checking extension class
 *     class_exists(\PhpAiToolkit\Doctest\DoctestExtension::class) // => true
 */

namespace PhpAiToolkit\Doctest;

use function dirname;
use function getcwd;

use PhpAiToolkit\Doctest\Configuration\Configuration;
use PhpAiToolkit\Doctest\Configuration\ConfigurationLoader;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration as PhpUnitConfiguration;
use PHPUnit\TextUI\Configuration\Registry;

/**
 * PHPUnit Extension for doctest.
 *
 * This extension loads doctest configuration from phpunit.xml parameters
 * and makes it available to DoctestSuite.
 *
 * @example Checking extension interface
 *     $implements = class_implements(\PhpAiToolkit\Doctest\DoctestExtension::class);
 *     in_array(\PHPUnit\Runner\Extension\Extension::class, $implements, true) // => true
 */
final class DoctestExtension implements Extension
{
    private static ?Configuration $configuration = null;

    /**
     * Bootstraps the extension.
     *
     * Called by PHPUnit when the extension is loaded. The facade is the handle
     * an extension registers subscribers through; doctest registers none, and
     * takes nothing from it. A configuration that switches doctest off is not
     * stored, leaving the run with whatever it already had.
     *
     * @param PhpUnitConfiguration $configuration PHPUnit configuration
     * @param Facade $facade PHPUnit extension facade
     * @param ParameterCollection $parameters extension parameters from phpunit.xml
     */
    public function bootstrap(
        PhpUnitConfiguration $configuration,
        Facade $facade,
        ParameterCollection $parameters,
    ): void {
        $config = ConfigurationLoader::fromParameters($parameters, self::basePath($configuration));
        if (!$config->isEnabled()) {
            return;
        }

        self::$configuration = $config;
    }

    /**
     * Returns the directory configured paths are resolved against.
     *
     * Paths in phpunit.xml are written relative to that file, so the directory
     * holding it is the base; a run without a configuration file falls back to
     * the working directory.
     *
     * @param PhpUnitConfiguration $configuration the configuration of the running test runner
     */
    public static function basePath(PhpUnitConfiguration $configuration): string
    {
        $configFile = $configuration->hasConfigurationFile() ? $configuration->configurationFile() : '';
        if ($configFile !== '') {
            return dirname($configFile);
        }

        $workingDirectory = getcwd();

        return $workingDirectory === false ? '' : $workingDirectory;
    }

    /**
     * Reads the parameters phpunit.xml declares for this extension.
     *
     * PHPUnit 10.5 builds the test suite before it bootstraps extensions, so a
     * suite asking bootstrap() for its configuration would be handed nothing
     * there and discover no examples. Reading the declaration gives the same
     * parameters PHPUnit passes to bootstrap(), at whatever point the suite
     * asks for them.
     *
     * @param PhpUnitConfiguration $configuration the configuration of the running test runner
     *
     * @return Configuration|null the declared configuration, or null when doctest is not declared or is switched off
     */
    public static function declaredConfiguration(PhpUnitConfiguration $configuration): ?Configuration
    {
        foreach ($configuration->extensionBootstrappers() as $bootstrapper) {
            if ($bootstrapper['className'] !== self::class) {
                continue;
            }

            $config = ConfigurationLoader::fromParameters(
                ParameterCollection::fromArray($bootstrapper['parameters']),
                self::basePath($configuration),
            );

            return $config->isEnabled() ? $config : null;
        }

        return null;
    }

    /**
     * Returns the configuration the run is working from.
     *
     * @return Configuration|null the configuration, or null if doctest is not configured
     */
    public static function getConfiguration(): ?Configuration
    {
        return self::$configuration ?? self::declaredConfiguration(Registry::get());
    }
}
