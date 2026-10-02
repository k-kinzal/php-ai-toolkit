<?php

declare(strict_types=1);

namespace Toolkit\Doctest\Configuration;

use function array_filter;
use function array_map;
use function array_values;
use function explode;

use const FILTER_VALIDATE_BOOLEAN;

use function filter_var;
use function getenv;

use PHPUnit\Runner\Extension\ParameterCollection;

use function strtoupper;

/**
 * Loads Configuration from PHPUnit extension parameters or from environment variables.
 *
 * Both sources carry the same five values under the same names. PHPUnit 10 and
 * later hand them to DoctestExtension as extension parameters. PHPUnit 9 has no
 * extension that could receive them before the test suite is built, so there
 * the php element of phpunit.xml exports each one as an environment variable
 * named after the parameter in upper case behind a DOCTEST_ prefix.
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
     * The parameter names every source understands.
     *
     * - directories: Comma-separated list of directories to scan
     * - files: Comma-separated list of files to scan
     * - exclude: Comma-separated list of glob patterns to exclude
     * - bootstrap: Path to bootstrap file
     * - enabled: Boolean to enable/disable doctest
     */
    public const PARAMETERS = ['directories', 'files', 'exclude', 'bootstrap', 'enabled'];

    /**
     * The prefix in front of the upper-cased parameter name in an environment variable.
     */
    public const ENVIRONMENT_PREFIX = 'DOCTEST_';

    /**
     * Creates a Configuration from PHPUnit extension parameters.
     *
     * @param ParameterCollection $parameters PHPUnit extension parameters
     * @param string $basePath base path for resolving relative paths
     */
    public static function fromParameters(ParameterCollection $parameters, string $basePath = ''): Configuration
    {
        $values = [];
        foreach (self::PARAMETERS as $name) {
            if ($parameters->has($name)) {
                $values[$name] = $parameters->get($name);
            }
        }

        return self::fromValues($values, $basePath);
    }

    /**
     * Creates a Configuration from the environment variables the php element of phpunit.xml sets.
     *
     * Each variable is a parameter name in upper case behind the DOCTEST_
     * prefix: DOCTEST_DIRECTORIES, DOCTEST_FILES, DOCTEST_EXCLUDE,
     * DOCTEST_BOOTSTRAP, and DOCTEST_ENABLED. A variable that is not set leaves
     * its parameter at the default.
     *
     * @param string $basePath base path for resolving relative paths
     *
     * @example Loading configuration from the environment
     *     putenv('DOCTEST_DIRECTORIES=src,lib');
     *     $config = \Toolkit\Doctest\Configuration\ConfigurationLoader::fromEnvironment('/app');
     *     putenv('DOCTEST_DIRECTORIES');
     *     $config->getDirectories() // => ['/app/src', '/app/lib']
     */
    public static function fromEnvironment(string $basePath = ''): Configuration
    {
        $values = [];
        foreach (self::PARAMETERS as $name) {
            $value = getenv(self::ENVIRONMENT_PREFIX . strtoupper($name));
            if ($value !== false) {
                $values[$name] = $value;
            }
        }

        return self::fromValues($values, $basePath);
    }

    /**
     * Creates a Configuration from parameter values keyed by parameter name.
     *
     * A name that is absent keeps its default: nothing to scan, nothing
     * excluded, no bootstrap, and doctest enabled.
     *
     * @param array<string, string> $values parameter values by name
     * @param string $basePath base path for resolving relative paths
     */
    public static function fromValues(array $values, string $basePath = ''): Configuration
    {
        $bootstrap = $values['bootstrap'] ?? null;

        return new Configuration(
            directories: self::paths($values['directories'] ?? null, $basePath),
            files: self::paths($values['files'] ?? null, $basePath),
            excludePatterns: self::patterns($values['exclude'] ?? null),
            bootstrap: $bootstrap === null ? null : Configuration::resolvePath($bootstrap, $basePath),
            enabled: !isset($values['enabled']) || filter_var($values['enabled'], FILTER_VALIDATE_BOOLEAN),
        );
    }

    /**
     * Splits one comma-separated path value and resolves each path against the base path.
     *
     * @param string|null $value the parameter value, or null when the parameter is absent
     *
     * @return list<string>
     */
    public static function paths(?string $value, string $basePath): array
    {
        return array_map(
            static fn (string $path): string => Configuration::resolvePath($path, $basePath),
            self::patterns($value),
        );
    }

    /**
     * Splits one comma-separated value into a list of trimmed, non-empty entries.
     *
     * @param string|null $value the parameter value, or null when the parameter is absent
     *
     * @return list<string>
     */
    public static function patterns(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $value)),
            static fn (string $entry): bool => $entry !== '',
        ));
    }
}
