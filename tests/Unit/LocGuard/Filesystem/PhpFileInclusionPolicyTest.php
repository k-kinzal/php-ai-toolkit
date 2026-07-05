<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Filesystem;

use PhpAiToolkit\LocGuard\Config\LimitConfig;
use PhpAiToolkit\LocGuard\Config\LocGuardConfig;
use PhpAiToolkit\LocGuard\Config\ReportConfig;
use PhpAiToolkit\LocGuard\Filesystem\LocGuardPathResolver;
use PhpAiToolkit\LocGuard\Filesystem\PhpFileInclusionPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
