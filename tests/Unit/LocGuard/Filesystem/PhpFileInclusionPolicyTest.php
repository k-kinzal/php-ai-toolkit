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

/**
 * @covers \Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Config\LocGuardConfig
 * @uses \Toolkit\LocGuard\Filesystem\LocGuardPathResolver
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 */
#[CoversClass(PhpFileInclusionPolicy::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(LocGuardConfig::class)]
#[UsesClass(LocGuardPathResolver::class)]
#[UsesClass(ReportConfig::class)]
final class PhpFileInclusionPolicyTest extends TestCase
{
    public function testIncludesReturnsTrueForIncludedPhpFile(): void
    {
        $config = new LocGuardConfig('/tmp/project', ['src'], [], new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20), new ReportConfig('ai', ['path']));

        self::assertTrue((new PhpFileInclusionPolicy())->includes($config, '/tmp/project/src/Example.php'));
    }

    public function testIncludesReturnsFalseForNonPhpAndExcludedFile(): void
    {
        $config = new LocGuardConfig('/tmp/project', ['src'], ['src/Generated/*'], new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20), new ReportConfig('ai', ['path']));

        self::assertFalse((new PhpFileInclusionPolicy())->includes($config, '/tmp/project/src/readme.txt'));
        self::assertFalse((new PhpFileInclusionPolicy())->includes($config, '/tmp/project/src/Generated/Skip.php'));
    }
}
