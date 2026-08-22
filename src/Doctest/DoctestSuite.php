<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest;

use Override;
use PhpAiToolkit\Doctest\Configuration\Configuration;
use PhpAiToolkit\Doctest\TestCase\DoctestRunner;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Medium;

/**
 * The test suite PHPUnit loads to run a project's documented examples.
 *
 * It is the one concrete DoctestRunner the package ships, so a project runs its
 * examples without writing a test class of its own. PHPUnit's testsuite element
 * takes a file rather than a class name, which is why this one is named by path
 * even though it is autoloadable like the rest of the source:
 *
 *     <testsuite name="doctest">
 *         <file>vendor/k-kinzal/php-ai-toolkit/src/Doctest/DoctestSuite.php</file>
 *     </testsuite>
 *
 *     <extensions>
 *         <bootstrap class="PhpAiToolkit\Doctest\DoctestExtension">
 *             <parameter name="directories" value="src"/>
 *         </bootstrap>
 *     </extensions>
 */
#[CoversNothing]
#[Medium]
final class DoctestSuite extends DoctestRunner
{
    /**
     * Returns the configuration the extension read from phpunit.xml.
     *
     * An unconfigured extension yields an empty configuration, which discovers
     * no examples rather than failing the run.
     */
    #[Override]
    public static function configure(): Configuration
    {
        return DoctestExtension::getConfiguration() ?? new Configuration(directories: []);
    }
}
