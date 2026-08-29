<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Filesystem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\Config\LocGuardConfig;
use Toolkit\LocGuard\Config\Policy\ApplyConfig;
use Toolkit\LocGuard\Config\Policy\PolicyConfig;
use Toolkit\LocGuard\Config\ReportConfig;
use Toolkit\LocGuard\Config\ScanConfig;
use Toolkit\LocGuard\Filesystem\FilePathPatternMatcher;
use Toolkit\LocGuard\Filesystem\LocGuardPathResolver;
use Toolkit\LocGuard\Filesystem\PhpFileFinder;
use Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy;
use Toolkit\LocGuard\Filesystem\PhpPathFileCollector;
use Toolkit\LocGuard\LocGuardException;

/**
 * @covers \Toolkit\LocGuard\Filesystem\PhpFileFinder
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Config\LocGuardConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyConfig
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyConfig
 * @uses \Toolkit\LocGuard\Config\ScanConfig
 * @uses \Toolkit\LocGuard\Filesystem\FilePathPatternMatcher
 * @uses \Toolkit\LocGuard\Filesystem\LocGuardPathResolver
 * @uses \Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \Toolkit\LocGuard\Filesystem\PhpPathFileCollector
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 */
#[CoversClass(PhpFileFinder::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(LocGuardConfig::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(FilePathPatternMatcher::class)]
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

        $limits = new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20, 20);
        $files = (new PhpFileFinder())->find(new LocGuardConfig(
            $dir,
            new ScanConfig(['src'], ['src/Generated/*']),
            ['standard' => new PolicyConfig('standard', null, $limits)],
            new ApplyConfig('standard', []),
            new ReportConfig('ai', ['path', 'line', 'rule']),
        ));

        self::assertSame([$dir . '/src/Example.php' => 'src/Example.php'], $files);
    }

    public function testFindAcceptsAbsoluteRootPath(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-finder-' . uniqid('', true);
        mkdir($dir);
        mkdir($dir . '/src');
        file_put_contents($dir . '/src/Example.php', '<?php');

        $limits = new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20, 20);
        $files = (new PhpFileFinder())->find(new LocGuardConfig(
            $dir,
            new ScanConfig([$dir . '/src'], []),
            ['standard' => new PolicyConfig('standard', null, $limits)],
            new ApplyConfig('standard', []),
            new ReportConfig('ai', ['path', 'line', 'rule']),
        ));

        self::assertSame([$dir . '/src/Example.php' => 'src/Example.php'], $files);
    }

    public function testFindReturnsEmptyForExcludedSingleFile(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-finder-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/Example.php', '<?php');

        $limits = new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20, 20);
        $files = (new PhpFileFinder())->find(new LocGuardConfig(
            $dir,
            new ScanConfig(['.'], ['Example.php']),
            ['standard' => new PolicyConfig('standard', null, $limits)],
            new ApplyConfig('standard', []),
            new ReportConfig('ai', ['path', 'line', 'rule']),
        ));

        self::assertSame([], $files);
    }

    public function testFindRejectsMissingPath(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-finder-' . uniqid('', true);
        mkdir($dir);

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('Configured scan root is not a directory');

        $limits = new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20, 20);
        (new PhpFileFinder())->find(new LocGuardConfig(
            $dir,
            new ScanConfig(['missing'], []),
            ['standard' => new PolicyConfig('standard', null, $limits)],
            new ApplyConfig('standard', []),
            new ReportConfig('ai', ['path', 'line', 'rule']),
        ));
    }
}
