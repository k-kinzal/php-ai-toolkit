<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Assignment;

use Guard\Config\Configuration;
use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Input\DirectoryListing;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\FileSet;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\PathPatternMatcher;
use Guard\Input\Selection;
use Guard\Input\StructuredFile;
use Guard\Policy\Assignment\ApplyRuleMatcher;
use Guard\Policy\Assignment\FilePolicyAssigner;
use Guard\Policy\Assignment\FilePolicyAssignment;
use Guard\Policy\Context;
use Guard\Policy\Definition\ApplyConfig;
use Guard\Policy\Definition\ApplyRuleConfig;
use Guard\Policy\Definition\LimitConfig;
use Guard\Policy\Definition\MetricsConfig;
use Guard\Policy\Definition\PolicyConfig;
use Guard\Policy\Definition\ScanConfig;
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use Guard\Policy\PolicyBinding;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Assignment\FilePolicyAssigner
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\PathPatternMatcher
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Policy\Assignment\ApplyRuleMatcher
 * @uses \Guard\Policy\Assignment\FilePolicyAssignment
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Policy\Definition\ApplyConfig
 * @uses \Guard\Policy\Definition\ApplyRuleConfig
 * @uses \Guard\Policy\Definition\PolicyConfig
 * @uses \Guard\Policy\Definition\LimitConfig
 * @uses \Guard\Policy\Definition\MetricsConfig
 * @uses \Guard\Policy\Definition\ScanConfig
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(FilePolicyAssigner::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(PathPatternMatcher::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(ApplyRuleMatcher::class)]
#[UsesClass(FilePolicyAssignment::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(MetricsConfig::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
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
