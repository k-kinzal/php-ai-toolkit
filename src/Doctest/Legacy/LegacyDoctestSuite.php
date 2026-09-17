<?php

declare(strict_types=1);

namespace Toolkit\Doctest\Legacy;

use function getcwd;

use Override;
use Toolkit\Doctest\Configuration\Configuration;
use Toolkit\Doctest\Configuration\ConfigurationLoader;
use Toolkit\Doctest\TestCase\Legacy\LegacyDoctestRunner;

/**
 * The test suite PHPUnit 9 loads to run a project's documented examples.
 *
 * It is the PHPUnit 9 counterpart of DoctestSuite, which cannot run on that
 * version. PHPUnit 9 reads test metadata from doc-comments, so the attribute
 * binding that suite's data provider goes unseen and its test method is
 * called without an example; and the extension that suite reads its
 * configuration from implements an interface PHPUnit 9 does not ship. This
 * suite binds the provider with an annotation through LegacyDoctestRunner and
 * takes its configuration from environment variables, which the php element
 * of phpunit.xml sets before PHPUnit 9 builds the test suite and runs the
 * provider. A consuming project points PHPUnit 9 at this installed file and
 * exports its scan roots:
 *
 *     <testsuite name="doctest">
 *         <file>vendor/k-kinzal/php-ai-toolkit/src/Doctest/Legacy/LegacyDoctestSuite.php</file>
 *     </testsuite>
 *
 *     <php>
 *         <env name="DOCTEST_DIRECTORIES" value="src"/>
 *     </php>
 *
 * Each variable is a parameter of DoctestExtension in upper case behind a
 * DOCTEST_ prefix: DOCTEST_DIRECTORIES, DOCTEST_FILES, DOCTEST_EXCLUDE,
 * DOCTEST_BOOTSTRAP, and DOCTEST_ENABLED. PHPUnit 9 does not expose where its
 * configuration file is, so a relative path resolves against the working
 * directory PHPUnit was started from.
 *
 * @coversNothing
 *
 * @medium
 */
final class LegacyDoctestSuite extends LegacyDoctestRunner
{
    /**
     * Returns the configuration the environment carries.
     *
     * An environment that sets nothing yields an empty configuration, which
     * discovers no examples rather than failing the run, and so does one that
     * switches doctest off.
     */
    #[Override]
    public static function configure(): Configuration
    {
        $workingDirectory = getcwd();
        $config = ConfigurationLoader::fromEnvironment($workingDirectory === false ? '' : $workingDirectory);

        return $config->isEnabled() ? $config : new Configuration(directories: []);
    }
}
