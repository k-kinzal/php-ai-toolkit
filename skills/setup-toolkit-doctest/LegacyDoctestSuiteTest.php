<?php

declare(strict_types=1);

namespace REPLACE_WITH_TEST_NAMESPACE;

use Override;
use Toolkit\Doctest\Configuration\Configuration;
use Toolkit\Doctest\TestCase\Legacy\LegacyDoctestRunner;

/**
 * Runs the project's documented examples on PHPUnit 9.
 *
 * @medium
 */
final class LegacyDoctestSuiteTest extends LegacyDoctestRunner
{
    /**
     * Selects the production autoload roots whose PHPDoc examples are executable.
     */
    #[Override]
    public static function configure(): Configuration
    {
        return new Configuration(
            directories: [dirname(__DIR__, 2) . '/REPLACE_WITH_PRODUCTION_ROOT'],
        );
    }
}
