<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Filesystem;

use PhpAiToolkit\TreeGuard\Config\ReportConfig;
use PhpAiToolkit\TreeGuard\Config\TreeGuardConfig;
use PhpAiToolkit\TreeGuard\Filesystem\PathInclusionPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\TreeGuard\Filesystem\PathInclusionPolicy
 * @uses \PhpAiToolkit\TreeGuard\Config\ReportConfig
 * @uses \PhpAiToolkit\TreeGuard\Config\TreeGuardConfig
 */
#[CoversClass(PathInclusionPolicy::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(TreeGuardConfig::class)]
final class PathInclusionPolicyTest extends TestCase
{
    public function testIncludesEveryPathWithoutExcludes(): void
    {
        $config = new TreeGuardConfig('/project', ['src'], [], [], new ReportConfig('ai', ['path', 'rule']));

        self::assertTrue((new PathInclusionPolicy())->includes($config, 'src/notes.txt'));
        self::assertTrue((new PathInclusionPolicy())->includes($config, 'src/Generated'));
    }

    public function testIncludesRejectsExcludedPaths(): void
    {
        $config = new TreeGuardConfig('/project', ['src'], ['src/Generated*', '*.tmp'], [], new ReportConfig('ai', ['path', 'rule']));

        self::assertFalse((new PathInclusionPolicy())->includes($config, 'src/Generated'));
        self::assertFalse((new PathInclusionPolicy())->includes($config, 'src/A/draft.tmp'));
        self::assertTrue((new PathInclusionPolicy())->includes($config, 'src/A/Kept.php'));
    }
}
