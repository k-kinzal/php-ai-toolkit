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
     * Called by PHPUnit when the extension is loaded.
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
        $config = ConfigurationLoader::fromParameters($parameters, $this->basePath($configuration));
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
     */
    public function basePath(PhpUnitConfiguration $configuration): string
    {
        $configFile = $configuration->hasConfigurationFile() ? $configuration->configurationFile() : '';
        if ($configFile !== '') {
            return dirname($configFile);
        }

        $workingDirectory = getcwd();

        return $workingDirectory === false ? '' : $workingDirectory;
    }

    /**
     * Returns the stored configuration.
     *
     * @return Configuration|null the configuration, or null if not initialized
     */
    public static function getConfiguration(): ?Configuration
    {
        return self::$configuration;
    }
}
