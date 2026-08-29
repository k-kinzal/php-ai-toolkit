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
use Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy;
use Toolkit\LocGuard\Filesystem\PhpPathFileCollector;
use Toolkit\LocGuard\LocGuardException;

/**
 * @covers \Toolkit\LocGuard\Filesystem\PhpPathFileCollector
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Config\LocGuardConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyConfig
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyConfig
 * @uses \Toolkit\LocGuard\Config\ScanConfig
 * @uses \Toolkit\LocGuard\Filesystem\FilePathPatternMatcher
 * @uses \Toolkit\LocGuard\Filesystem\LocGuardPathResolver
 * @uses \Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 */
#[CoversClass(PhpPathFileCollector::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(LocGuardConfig::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(FilePathPatternMatcher::class)]
#[UsesClass(LocGuardPathResolver::class)]
#[UsesClass(PhpFileInclusionPolicy::class)]
#[UsesClass(ReportConfig::class)]
final class PhpPathFileCollectorTest extends TestCase
{
    public function testFilesReturnsIncludedFilesInDirectory(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-collector-' . uniqid('', true);
        mkdir($dir . '/src', 0755, true);
        file_put_contents($dir . '/src/Example.php', '<?php');
        file_put_contents($dir . '/src/readme.txt', 'text');
        $limits = new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20, 20);
        $config = new LocGuardConfig($dir, new ScanConfig(['src'], []), ['standard' => new PolicyConfig('standard', null, $limits)], new ApplyConfig('standard', []), new ReportConfig('ai', ['path']));

        self::assertSame([$dir . '/src/Example.php' => 'src/Example.php'], (new PhpPathFileCollector())->files($config, $dir . '/src'));
    }

    public function testFilesThrowsForMissingPath(): void
    {
        $limits = new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20, 20);
        $config = new LocGuardConfig('/tmp/project', new ScanConfig(['src'], []), ['standard' => new PolicyConfig('standard', null, $limits)], new ApplyConfig('standard', []), new ReportConfig('ai', ['path']));

        $this->expectException(LocGuardException::class);

        (new PhpPathFileCollector())->files($config, '/tmp/project/missing');
    }
}
