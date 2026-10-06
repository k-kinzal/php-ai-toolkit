<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Assignment;

use Guard\Collect\Matching\PathPatternMatcher;
use Guard\Config\Assignment\ApplyRuleMatcher;
use Guard\Config\Assignment\FilePolicyAssigner;
use Guard\Config\Assignment\FilePolicyAssignment;
use Guard\Config\Profile\ApplyConfig;
use Guard\Config\Profile\ApplyRuleConfig;
use Guard\Config\Profile\PolicyConfig;
use Guard\Config\Value\LimitConfig;
use Guard\Config\Value\MetricsConfig;
use Guard\Config\Value\ScanConfig;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Assignment\FilePolicyAssigner
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Matching\PathPatternMatcher
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Assignment\ApplyRuleMatcher
 * @uses \Guard\Config\Assignment\FilePolicyAssignment
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Profile\ApplyConfig
 * @uses \Guard\Config\Profile\ApplyRuleConfig
 * @uses \Guard\Config\Profile\PolicyConfig
 * @uses \Guard\Config\Value\LimitConfig
 * @uses \Guard\Config\Value\MetricsConfig
 * @uses \Guard\Config\Value\ScanConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(FilePolicyAssigner::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(PathPatternMatcher::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(ApplyRuleMatcher::class)]
#[UsesClass(FilePolicyAssignment::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(MetricsConfig::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
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
