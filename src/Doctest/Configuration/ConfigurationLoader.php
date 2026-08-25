<?php

declare(strict_types=1);

namespace Toolkit\Doctest\Configuration;

use function array_filter;
use function array_map;
use function array_values;
use function explode;

use const FILTER_VALIDATE_BOOLEAN;

use function filter_var;

use PHPUnit\Runner\Extension\ParameterCollection;

/**
 * Loads Configuration from PHPUnit extension parameters.
 *
 * This loader converts PHPUnit extension parameters into a Configuration object,
 * handling path resolution and parameter parsing.
 *
 * @example Loading configuration from parameters
 *     $params = \PHPUnit\Runner\Extension\ParameterCollection::fromArray([
 *         'directories' => 'src,lib',
 *         'exclude' => '*Test.php',
 *     ]);
 *     $config = \Toolkit\Doctest\Configuration\ConfigurationLoader::fromParameters($params, '/app');
 *     $config->getDirectories() // => ['/app/src', '/app/lib']
 *     $config->getExcludePatterns() // => ['*Test.php']
 */
final class ConfigurationLoader
{
    /**
     * Creates a Configuration from PHPUnit extension parameters.
     *
     * Supported parameters:
     * - directories: Comma-separated list of directories to scan
     * - files: Comma-separated list of files to scan
     * - exclude: Comma-separated list of glob patterns to exclude
     * - bootstrap: Path to bootstrap file
     * - enabled: Boolean to enable/disable doctest
     *
     * @param ParameterCollection $parameters PHPUnit extension parameters
     * @param string $basePath base path for resolving relative paths
     */
    public static function fromParameters(ParameterCollection $parameters, string $basePath = ''): Configuration
    {
        return new Configuration(
            directories: self::paths($parameters, 'directories', $basePath),
            files: self::paths($parameters, 'files', $basePath),
            excludePatterns: self::patterns($parameters, 'exclude'),
            bootstrap: $parameters->has('bootstrap') ? Configuration::resolvePath($parameters->get('bootstrap'), $basePath) : null,
            enabled: !$parameters->has('enabled') || filter_var($parameters->get('enabled'), FILTER_VALIDATE_BOOLEAN),
        );
    }

    /**
     * Reads one comma-separated path parameter, resolved against the base path.
     *
     * @return list<string>
     */
    public static function paths(ParameterCollection $parameters, string $name, string $basePath): array
    {
        return array_map(
            static fn (string $path): string => Configuration::resolvePath($path, $basePath),
            self::patterns($parameters, $name),
        );
    }

    /**
     * Reads one comma-separated parameter into a list of trimmed values.
     *
     * @return list<string>
     */
    public static function patterns(ParameterCollection $parameters, string $name): array
    {
        if (!$parameters->has($name)) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $parameters->get($name))),
            static fn (string $value): bool => $value !== '',
        ));
    }
}
