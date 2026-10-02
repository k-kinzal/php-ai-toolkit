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

        self::assertSame([dirname(__DIR__, 5) . '/src', dirname(__DIR__, 5) . '/packages/guard-php/src', dirname(__DIR__, 5) . '/packages/docgen-php/src', dirname(__DIR__, 5) . '/packages/phpstan-guard-rules/src', dirname(__DIR__, 5) . '/packages/phpunit-ai-reporter/src', dirname(__DIR__, 5) . '/packages/phpstan-ai-formatter/src'], $config->getDirectories());
        self::assertSame(DoctestExtension::getConfiguration()?->getDirectories(), $config->getDirectories());
    }
}
