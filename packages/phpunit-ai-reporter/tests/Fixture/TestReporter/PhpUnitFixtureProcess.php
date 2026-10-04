<?php

declare(strict_types=1);

namespace Tests\Fixture\TestReporter;

use const PHP_BINARY;

use RuntimeException;

use function dirname;
use function fclose;
use function is_file;
use function is_resource;
use function is_string;
use function proc_close;
use function proc_open;
use function stream_get_contents;

/**
 * Runs the installed PHPUnit against a fixture configuration in this directory.
 *
 * The binary is the nearest vendor/bin/phpunit above the fixture, so the same
 * tests run from a package checkout and from a repository that installs
 * dependencies above the package.
 */
final class PhpUnitFixtureProcess
{
    /**
     * Runs phpunit-extension.xml.dist and returns its status and output.
     *
     * @param array<string, string> $environment environment of the PHPUnit process
     */
    public static function runExtension(array $environment): PhpUnitFixtureResult
    {
        return self::run(__DIR__ . '/phpunit-extension.xml.dist', $environment);
    }

    /**
     * Runs phpunit-listener.xml.dist and returns its status and output.
     *
     * @param array<string, string> $environment environment of the PHPUnit process
     */
    public static function runListener(array $environment): PhpUnitFixtureResult
    {
        return self::run(__DIR__ . '/phpunit-listener.xml.dist', $environment);
    }

    /**
     * @param array<string, string> $environment environment of the PHPUnit process
     */
    private static function run(string $configurationFile, array $environment): PhpUnitFixtureResult
    {
        $pipes = [];
        $process = proc_open(
            [
                PHP_BINARY,
                self::phpunitBinary($configurationFile),
                '--configuration',
                $configurationFile,
                '--colors=never',
            ],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            dirname($configurationFile),
            $environment,
        );
        if (!is_resource($process)) {
            throw new RuntimeException('PHPUnit process did not start.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        if (!is_string($stdout) || !is_string($stderr)) {
            throw new RuntimeException('PHPUnit output could not be read.');
        }

        return new PhpUnitFixtureResult($exitCode, $stdout . $stderr);
    }

    /**
     * Returns the nearest vendor/bin/phpunit at or above the configuration file.
     */
    private static function phpunitBinary(string $configurationFile): string
    {
        $directory = dirname($configurationFile);
        while (!is_file($directory . '/vendor/bin/phpunit')) {
            $parent = dirname($directory);
            if ($parent === $directory) {
                throw new RuntimeException('vendor/bin/phpunit was not found.');
            }
            $directory = $parent;
        }

        return $directory . '/vendor/bin/phpunit';
    }
}
