<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\Doctest\Configuration\Configuration;
use Toolkit\Doctest\Configuration\ConfigurationLoader;
use Toolkit\Doctest\DoctestExtension;
use Toolkit\Doctest\DoctestSuite;

/**
 * @covers \Toolkit\Doctest\DoctestSuite
 * @uses \Toolkit\Doctest\Configuration\Configuration
 * @uses \Toolkit\Doctest\Configuration\ConfigurationLoader
 * @uses \Toolkit\Doctest\DoctestExtension
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
        $extensionConfig = DoctestExtension::getConfiguration();

        self::assertNotNull($extensionConfig);
        self::assertSame($extensionConfig->getDirectories(), $config->getDirectories());
    }
}
