<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest;

use PhpAiToolkit\Doctest\Configuration\Configuration;
use PhpAiToolkit\Doctest\Configuration\ConfigurationLoader;
use PhpAiToolkit\Doctest\DoctestExtension;
use PhpAiToolkit\Doctest\DoctestSuite;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\Doctest\DoctestSuite
 * @uses \PhpAiToolkit\Doctest\Configuration\Configuration
 * @uses \PhpAiToolkit\Doctest\Configuration\ConfigurationLoader
 * @uses \PhpAiToolkit\Doctest\DoctestExtension
 */
#[CoversClass(DoctestSuite::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationLoader::class)]
#[UsesClass(DoctestExtension::class)]
final class DoctestSuiteTest extends TestCase
{
    public function testConfigureHandsBackWhatTheExtensionRead(): void
    {
        $config = DoctestSuite::configure();

        self::assertSame([dirname(__DIR__, 3) . '/src'], $config->getDirectories());
        self::assertSame(DoctestExtension::getConfiguration()?->getDirectories(), $config->getDirectories());
    }
}
