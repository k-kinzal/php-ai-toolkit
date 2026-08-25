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
use Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy;
use Toolkit\LocGuard\Filesystem\PhpPathFileCollector;
use Toolkit\LocGuard\LocGuardException;

/**
 * @covers \Toolkit\LocGuard\Filesystem\PhpPathFileCollector
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Config\LocGuardConfig
 * @uses \Toolkit\LocGuard\Filesystem\LocGuardPathResolver
 * @uses \Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 */
#[CoversClass(PhpPathFileCollector::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(LocGuardConfig::class)]
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
        $config = new LocGuardConfig($dir, ['src'], [], new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20), new ReportConfig('ai', ['path']));

        self::assertSame([$dir . '/src/Example.php' => 'src/Example.php'], (new PhpPathFileCollector())->files($config, $dir . '/src'));
    }

    public function testFilesThrowsForMissingPath(): void
    {
        $config = new LocGuardConfig('/tmp/project', ['src'], [], new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20), new ReportConfig('ai', ['path']));

        $this->expectException(LocGuardException::class);

        (new PhpPathFileCollector())->files($config, '/tmp/project/missing');
    }
}
