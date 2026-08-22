<?php

declare(strict_types=1);

namespace Tests\Doctest;

use Override;
use PhpAiToolkit\Doctest\Configuration\Configuration;
use PhpAiToolkit\Doctest\TestCase\Legacy\LegacyDoctestRunner;

/**
 * Runs this package's documented examples on PHPUnit 9.
 *
 * PHPUnit 9 has no extension API, so the suite states its own configuration
 * where DoctestSuite reads one from phpunit.xml. Only the PHPUnit 9
 * configuration runs this directory.
 *
 * @medium
 */
final class LegacyDoctestSuiteTest extends LegacyDoctestRunner
{
    /**
     * Returns the configuration the PHPUnit 10+ suite gets from phpunit.xml.
     *
     * The extension and the loader that reads its parameters document the
     * PHPUnit 10 extension API, which PHPUnit 9 does not ship, so their
     * examples cannot run on this version and their files are left unscanned.
     */
    #[Override]
    public static function configure(): Configuration
    {
        return new Configuration(
            directories: [dirname(__DIR__, 2) . '/src'],
            excludePatterns: ['*/Doctest/DoctestExtension.php', '*/Doctest/Configuration/ConfigurationLoader.php'],
        );
    }
}
