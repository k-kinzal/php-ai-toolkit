<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Loc\Assignment;

use Guard\Collect\Php\Filesystem\FilePathPatternMatcher;
use Guard\Config\Loc\LimitConfig;
use Guard\Config\Loc\MetricsConfig;
use Guard\Config\Loc\Policy\ApplyConfig;
use Guard\Config\Loc\Policy\ApplyRuleConfig;
use Guard\Config\Loc\Policy\PolicyConfig;
use Guard\Config\Loc\ScanConfig;
use Guard\Policy\Loc\Assignment\ApplyRuleMatcher;
use Guard\Policy\Loc\Assignment\FilePolicyAssigner;
use Guard\Policy\Loc\Assignment\FilePolicyAssignment;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Loc\Assignment\FilePolicyAssigner
 * @uses \Guard\Collect\Php\Filesystem\FilePathPatternMatcher
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
 * @uses \Guard\Policy\Loc\Assignment\ApplyRuleMatcher
 * @uses \Guard\Policy\Loc\Assignment\FilePolicyAssignment
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(FilePolicyAssigner::class)]
#[UsesClass(FilePathPatternMatcher::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(MetricsConfig::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(ApplyRuleMatcher::class)]
#[UsesClass(FilePolicyAssignment::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class FilePolicyAssignerTest extends TestCase
{
    public function testAssignSelectsDefaultAndPathSpecificPolicies(): void
    {
        $standard = new PolicyConfig('standard', null, LimitConfig::fromValues(['file.lines' => 500]));
        $native = new PolicyConfig('native-api', 'standard', LimitConfig::fromValues(['file.lines' => 900]));
        $config = new MetricsConfig(
            '/project',
            new ScanConfig(['src'], []),
            ['standard' => $standard, 'native-api' => $native],
            new ApplyConfig('standard', [new ApplyRuleConfig('native', ['src/Native.php'], 'native-api')]),
        );
        $assignments = (new FilePolicyAssigner())->assign($config, [
            '/project/src/Example.php' => 'src/Example.php',
            '/project/src/Native.php' => 'src/Native.php',
        ]);

        self::assertSame('standard', $assignments[0]->policy->name);
        self::assertSame('native-api', $assignments[1]->policy->name);
    }

    public function testAssignFileRejectsAmbiguousRules(): void
    {
        $policy = new PolicyConfig('standard', null, LimitConfig::fromValues(['file.lines' => 500]));
        $config = new MetricsConfig(
            '/project',
            new ScanConfig(['src'], []),
            ['standard' => $policy],
            new ApplyConfig('standard', [
                new ApplyRuleConfig('php', ['src/*.php'], 'standard'),
                new ApplyRuleConfig('example', ['src/Example.php'], 'standard'),
            ]),
        );

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('matches multiple apply rules');
        (new FilePolicyAssigner())->assignFile($config, '/project/src/Example.php', 'src/Example.php');
    }

    public function testAssignRejectsRuleMatchingNoScannedFile(): void
    {
        $standard = new PolicyConfig('standard', null, LimitConfig::fromValues(['file.lines' => 500]));
        $native = new PolicyConfig('native', 'standard', LimitConfig::fromValues(['file.lines' => 900]));
        $config = new MetricsConfig(
            '/project',
            new ScanConfig(['src'], []),
            ['standard' => $standard, 'native' => $native],
            new ApplyConfig('standard', [new ApplyRuleConfig('native', ['src/Native.php'], 'native')]),
        );

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('matches no scanned PHP files');
        (new FilePolicyAssigner())->assign($config, [
            '/project/src/Example.php' => 'src/Example.php',
        ]);
    }

    public function testAssignRejectsEmptyPhpScan(): void
    {
        $policy = new PolicyConfig('standard', null, LimitConfig::fromValues(['file.lines' => 500]));
        $config = new MetricsConfig(
            '/project',
            new ScanConfig(['src'], []),
            ['standard' => $policy],
            new ApplyConfig('standard', []),
        );

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('contain no PHP files');
        (new FilePolicyAssigner())->assign($config, []);
    }
}
