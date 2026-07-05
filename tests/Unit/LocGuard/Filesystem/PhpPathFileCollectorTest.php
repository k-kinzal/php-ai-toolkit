<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Filesystem;

use PhpAiToolkit\LocGuard\Config\LimitConfig;
use PhpAiToolkit\LocGuard\Config\LocGuardConfig;
use PhpAiToolkit\LocGuard\Config\ReportConfig;
use PhpAiToolkit\LocGuard\Filesystem\LocGuardPathResolver;
use PhpAiToolkit\LocGuard\Filesystem\PhpFileInclusionPolicy;
use PhpAiToolkit\LocGuard\Filesystem\PhpPathFileCollector;
use PhpAiToolkit\LocGuard\LocGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
        $dir = sys_get_temp_dir() . '/locguard-collector-' . bin2hex(random_bytes(4));
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
