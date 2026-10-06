<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Tree\Filesystem;

use Guard\Collect\Tree\Filesystem\PathInclusionPolicy;
use Guard\Config\Tree\StructureConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Tree\Filesystem\PathInclusionPolicy
 * @uses \Guard\Config\Tree\RuleConfig
 * @uses \Guard\Config\Tree\StructureConfig
 */
#[CoversClass(PathInclusionPolicy::class)]
#[UsesClass(\Guard\Config\Tree\RuleConfig::class)]
#[UsesClass(StructureConfig::class)]
final class PathInclusionPolicyTest extends TestCase
{
    public function testIncludesEveryPathWithoutExcludes(): void
    {
        $config = new StructureConfig('/project', ['src'], [], []);

        self::assertTrue((new PathInclusionPolicy())->includes($config, 'src/notes.txt'));
        self::assertTrue((new PathInclusionPolicy())->includes($config, 'src/Generated'));
    }

    public function testIncludesRejectsExcludedPaths(): void
    {
        $config = new StructureConfig('/project', ['src'], ['src/Generated*', '*.tmp'], []);

        self::assertFalse((new PathInclusionPolicy())->includes($config, 'src/Generated'));
        self::assertFalse((new PathInclusionPolicy())->includes($config, 'src/A/draft.tmp'));
        self::assertTrue((new PathInclusionPolicy())->includes($config, 'src/A/Kept.php'));
    }
}
