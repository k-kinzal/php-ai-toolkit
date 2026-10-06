<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Php\Filesystem;

use Guard\Collect\Php\Filesystem\FilePathPatternMatcher;
use Guard\Collect\Php\Filesystem\PathResolver;
use Guard\Collect\Php\Filesystem\PhpFileFinder;
use Guard\Collect\Php\Filesystem\PhpFileInclusionPolicy;
use Guard\Collect\Php\Filesystem\PhpPathFileCollector;
use Guard\Config\Loc\LimitConfig;
use Guard\Config\Loc\MetricsConfig;
use Guard\Config\Loc\Policy\ApplyConfig;
use Guard\Config\Loc\Policy\PolicyConfig;
use Guard\Config\Loc\ScanConfig;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Php\Filesystem\PhpFileFinder
 * @uses \Guard\Collect\Php\Filesystem\FilePathPatternMatcher
 * @uses \Guard\Collect\Php\Filesystem\PathResolver
 * @uses \Guard\Collect\Php\Filesystem\PhpFileInclusionPolicy
 * @uses \Guard\Collect\Php\Filesystem\PhpPathFileCollector
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Config\Loc\MetricsConfig
 * @uses \Guard\Config\Loc\Policy\ApplyConfig
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfig
 * @uses \Guard\Config\Loc\Policy\PolicyConfig
 * @uses \Guard\Config\Loc\ScanConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(PhpFileFinder::class)]
#[UsesClass(FilePathPatternMatcher::class)]
#[UsesClass(PathResolver::class)]
#[UsesClass(PhpFileInclusionPolicy::class)]
#[UsesClass(PhpPathFileCollector::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(MetricsConfig::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(\Guard\Config\Loc\Policy\ApplyRuleConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
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
        $files = (new PhpFileFinder())->find(new MetricsConfig(
            $dir,
            new ScanConfig(['src'], ['src/Generated/*']),
            ['standard' => new PolicyConfig('standard', null, $limits)],
            new ApplyConfig('standard', []),
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
        $files = (new PhpFileFinder())->find(new MetricsConfig(
            $dir,
            new ScanConfig([$dir . '/src'], []),
            ['standard' => new PolicyConfig('standard', null, $limits)],
            new ApplyConfig('standard', []),
        ));

        self::assertSame([$dir . '/src/Example.php' => 'src/Example.php'], $files);
    }

    public function testFindReturnsEmptyForExcludedSingleFile(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-finder-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/Example.php', '<?php');

        $limits = new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20, 20);
        $files = (new PhpFileFinder())->find(new MetricsConfig(
            $dir,
            new ScanConfig(['.'], ['Example.php']),
            ['standard' => new PolicyConfig('standard', null, $limits)],
            new ApplyConfig('standard', []),
        ));

        self::assertSame([], $files);
    }

    public function testFindRejectsMissingPath(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-finder-' . uniqid('', true);
        mkdir($dir);

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Configured scan root is not a directory');

        $limits = new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20, 20);
        (new PhpFileFinder())->find(new MetricsConfig(
            $dir,
            new ScanConfig(['missing'], []),
            ['standard' => new PolicyConfig('standard', null, $limits)],
            new ApplyConfig('standard', []),
        ));
    }
}
