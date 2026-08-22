<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest;

use Override;
use PhpAiToolkit\Doctest\Configuration\Configuration;
use PhpAiToolkit\Doctest\TestCase\DoctestRunner;
use PHPUnit\Framework\Attributes\Medium;

/**
 * PHPUnit test suite file for doctest.
 *
 * Add this file to your phpunit.xml testsuite configuration:
 *
 *     <testsuite name="doctest">
 *         <file>vendor/k-kinzal/php-ai-toolkit/DoctestSuite.php</file>
 *     </testsuite>
 *
 * And configure the extension:
 *
 *     <extensions>
 *         <bootstrap class="PhpAiToolkit\Doctest\DoctestExtension">
 *             <parameter name="directories" value="src"/>
 *         </bootstrap>
 *     </extensions>
 */
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
