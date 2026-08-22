<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Config;

use PhpAiToolkit\Doctest\Config\DoctestConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DoctestConfig::class)]
final class DoctestConfigTest extends TestCase
{
    public function testBootstrapPathResolvesRelativeToTheRoot(): void
    {
        self::assertSame('/app/vendor/autoload.php', (new DoctestConfig('/app', ['src'], [], 'vendor/autoload.php'))->bootstrapPath());
        self::assertSame('/opt/boot.php', (new DoctestConfig('/app', ['src'], [], '/opt/boot.php'))->bootstrapPath());
        self::assertNull((new DoctestConfig('/app'))->bootstrapPath());
    }

    public function testExposesWhatIsScannedAndScansSourceByDefault(): void
    {
        $config = new DoctestConfig('/app', ['src', 'lib'], ['src/Generated/*'], 'boot.php');

        self::assertSame('/app', $config->root);
        self::assertSame(['src', 'lib'], $config->paths);
        self::assertSame(['src/Generated/*'], $config->exclude);
        self::assertSame('boot.php', $config->bootstrap);
        self::assertSame(['src'], (new DoctestConfig('/app'))->paths);
        self::assertSame([], (new DoctestConfig('/app'))->exclude);
    }
}
