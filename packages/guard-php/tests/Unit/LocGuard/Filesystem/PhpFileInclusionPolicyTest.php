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

/**
 * @covers \Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Config\LocGuardConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyConfig
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyConfig
 * @uses \Toolkit\LocGuard\Config\ScanConfig
 * @uses \Toolkit\LocGuard\Filesystem\FilePathPatternMatcher
 * @uses \Toolkit\LocGuard\Filesystem\LocGuardPathResolver
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 */
#[CoversClass(PhpFileInclusionPolicy::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(LocGuardConfig::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(FilePathPatternMatcher::class)]
#[UsesClass(LocGuardPathResolver::class)]
#[UsesClass(ReportConfig::class)]
final class PhpFileInclusionPolicyTest extends TestCase
{
    public function testIncludesReturnsTrueForIncludedPhpFile(): void
    {
        $limits = new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20, 20);
        $config = new LocGuardConfig('/tmp/project', new ScanConfig(['src'], []), ['standard' => new PolicyConfig('standard', null, $limits)], new ApplyConfig('standard', []), new ReportConfig('ai', ['path']));

        self::assertTrue((new PhpFileInclusionPolicy())->includes($config, '/tmp/project/src/Example.php'));
    }

    public function testIncludesReturnsFalseForNonPhpAndExcludedFile(): void
    {
        $limits = new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20, 20);
        $config = new LocGuardConfig('/tmp/project', new ScanConfig(['src'], ['src/Generated/*']), ['standard' => new PolicyConfig('standard', null, $limits)], new ApplyConfig('standard', []), new ReportConfig('ai', ['path']));

        self::assertFalse((new PhpFileInclusionPolicy())->includes($config, '/tmp/project/src/readme.txt'));
        self::assertFalse((new PhpFileInclusionPolicy())->includes($config, '/tmp/project/src/Generated/Skip.php'));
    }
}
