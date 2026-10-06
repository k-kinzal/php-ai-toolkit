<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Php\Filesystem;

use Guard\Collect\Php\Filesystem\FilePathPatternMatcher;
use Guard\Collect\Php\Filesystem\PathResolver;
use Guard\Collect\Php\Filesystem\PhpFileInclusionPolicy;
use Guard\Config\Loc\LimitConfig;
use Guard\Config\Loc\MetricsConfig;
use Guard\Config\Loc\Policy\ApplyConfig;
use Guard\Config\Loc\Policy\PolicyConfig;
use Guard\Config\Loc\ScanConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Php\Filesystem\PhpFileInclusionPolicy
 * @uses \Guard\Collect\Php\Filesystem\FilePathPatternMatcher
 * @uses \Guard\Collect\Php\Filesystem\PathResolver
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Config\Loc\MetricsConfig
 * @uses \Guard\Config\Loc\Policy\ApplyConfig
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfig
 * @uses \Guard\Config\Loc\Policy\PolicyConfig
 * @uses \Guard\Config\Loc\ScanConfig
 */
#[CoversClass(PhpFileInclusionPolicy::class)]
#[UsesClass(FilePathPatternMatcher::class)]
#[UsesClass(PathResolver::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(MetricsConfig::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(\Guard\Config\Loc\Policy\ApplyRuleConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(ScanConfig::class)]
final class PhpFileInclusionPolicyTest extends TestCase
{
    public function testIncludesReturnsTrueForIncludedPhpFile(): void
    {
        $limits = new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20, 20);
        $config = new MetricsConfig('/tmp/project', new ScanConfig(['src'], []), ['standard' => new PolicyConfig('standard', null, $limits)], new ApplyConfig('standard', []));

        self::assertTrue((new PhpFileInclusionPolicy())->includes($config, '/tmp/project/src/Example.php'));
    }

    public function testIncludesReturnsFalseForNonPhpAndExcludedFile(): void
    {
        $limits = new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20, 20);
        $config = new MetricsConfig('/tmp/project', new ScanConfig(['src'], ['src/Generated/*']), ['standard' => new PolicyConfig('standard', null, $limits)], new ApplyConfig('standard', []));

        self::assertFalse((new PhpFileInclusionPolicy())->includes($config, '/tmp/project/src/readme.txt'));
        self::assertFalse((new PhpFileInclusionPolicy())->includes($config, '/tmp/project/src/Generated/Skip.php'));
    }
}
