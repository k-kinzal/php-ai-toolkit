<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Filesystem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\Config\LocGuardConfig;
use Toolkit\LocGuard\Config\ReportConfig;
use Toolkit\LocGuard\Filesystem\LocGuardPathResolver;
use Toolkit\LocGuard\Filesystem\PhpFileFinder;
use Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy;
use Toolkit\LocGuard\Filesystem\PhpPathFileCollector;
use Toolkit\LocGuard\LocGuardException;

/**
 * @covers \Toolkit\LocGuard\Filesystem\PhpFileFinder
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Config\LocGuardConfig
 * @uses \Toolkit\LocGuard\Filesystem\LocGuardPathResolver
 * @uses \Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \Toolkit\LocGuard\Filesystem\PhpPathFileCollector
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 */
#[CoversClass(PhpFileFinder::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(LocGuardConfig::class)]
#[UsesClass(LocGuardPathResolver::class)]
#[UsesClass(PhpFileInclusionPolicy::class)]
#[UsesClass(PhpPathFileCollector::class)]
#[UsesClass(ReportConfig::class)]
final class PhpFileFinderTest extends TestCase
{
    public function testFindsPhpFilesAndAppliesExcludes(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-finder-' . uniqid('', true);
        mkdir($dir);
        mkdir($dir . '/src');
        mkdir($dir . '/src/Generated');
        file_put_contents($dir . '/src/Example.php', '<?php');
        file_put_contents($dir . '/src/Generated/Skip.php', '<?php');
        file_put_contents($dir . '/src/readme.txt', 'not php');

        $files = (new PhpFileFinder())->find(new LocGuardConfig(
            $dir,
            ['src'],
            ['src/Generated/*'],
            new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20),
            new ReportConfig('ai', ['path', 'line', 'rule']),
        ));

        self::assertSame([$dir . '/src/Example.php' => 'src/Example.php'], $files);
    }

    public function testFindAcceptsAbsoluteFilePath(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-finder-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/Example.php', '<?php');

        $files = (new PhpFileFinder())->find(new LocGuardConfig(
            $dir,
            [$dir . '/Example.php'],
            [],
            new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20),
            new ReportConfig('ai', ['path', 'line', 'rule']),
        ));

        self::assertSame([$dir . '/Example.php' => 'Example.php'], $files);
    }

    public function testFindReturnsEmptyForExcludedSingleFile(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-finder-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/Example.php', '<?php');

        $files = (new PhpFileFinder())->find(new LocGuardConfig(
            $dir,
            ['Example.php'],
            ['Example.php'],
            new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20),
            new ReportConfig('ai', ['path', 'line', 'rule']),
        ));

        self::assertSame([], $files);
    }

    public function testFindRejectsMissingPath(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-finder-' . uniqid('', true);
        mkdir($dir);

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('Configured path does not exist');

        (new PhpFileFinder())->find(new LocGuardConfig(
            $dir,
            ['missing'],
            [],
            new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20),
            new ReportConfig('ai', ['path', 'line', 'rule']),
        ));
    }
}
