<?php

declare(strict_types=1);

namespace Tests\Unit\Compatibility;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Toolkit\Compatibility\IgnoreErrorExtension as CompatibilityIgnoreErrorExtension;

/**
 * @coversNothing
 */
#[CoversNothing]
final class IgnoreErrorExtensionTest extends TestCase
{
    public function testPhpStanContractIsAvailableOnEverySupportedVersion(): void
    {
        self::assertTrue(interface_exists('PHPStan\\Analyser\\IgnoreErrorExtension'));
    }

    public function testShouldIgnoreContractIsDeclared(): void
    {
        self::assertContains('shouldIgnore', get_class_methods(CompatibilityIgnoreErrorExtension::class));
    }
}
