<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Analysis\ApplyRuleMatcher;
use Toolkit\LocGuard\Analysis\FilePolicyAssigner;
use Toolkit\LocGuard\Analysis\FilePolicyAssignment;
use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\Config\LocGuardConfig;
use Toolkit\LocGuard\Config\Policy\ApplyConfig;
use Toolkit\LocGuard\Config\Policy\ApplyRuleConfig;
use Toolkit\LocGuard\Config\Policy\PolicyConfig;
use Toolkit\LocGuard\Config\ReportConfig;
use Toolkit\LocGuard\Config\ScanConfig;
use Toolkit\LocGuard\Filesystem\FilePathPatternMatcher;
use Toolkit\LocGuard\LocGuardException;

#[CoversClass(FilePolicyAssigner::class)]
#[UsesClass(ApplyRuleMatcher::class)]
#[UsesClass(FilePolicyAssignment::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(LocGuardConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(FilePathPatternMatcher::class)]
final class FilePolicyAssignerTest extends TestCase
{
    public function testAssignSelectsDefaultAndPathSpecificPolicies(): void
    {
        $standard = new PolicyConfig('standard', null, LimitConfig::fromValues(['file.lines' => 500]));
        $native = new PolicyConfig('native-api', 'standard', LimitConfig::fromValues(['file.lines' => 900]));
        $config = new LocGuardConfig(
            '/project',
            new ScanConfig(['src'], []),
            ['standard' => $standard, 'native-api' => $native],
            new ApplyConfig('standard', [new ApplyRuleConfig('native', ['src/Native.php'], 'native-api')]),
            new ReportConfig('ai', ['path']),
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
        $config = new LocGuardConfig(
            '/project',
            new ScanConfig(['src'], []),
            ['standard' => $policy],
            new ApplyConfig('standard', [
                new ApplyRuleConfig('php', ['src/*.php'], 'standard'),
                new ApplyRuleConfig('example', ['src/Example.php'], 'standard'),
            ]),
            new ReportConfig('ai', ['path']),
        );

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('matches multiple apply rules');
        (new FilePolicyAssigner())->assignFile($config, '/project/src/Example.php', 'src/Example.php');
    }

    public function testAssignRejectsRuleMatchingNoScannedFile(): void
    {
        $standard = new PolicyConfig('standard', null, LimitConfig::fromValues(['file.lines' => 500]));
        $native = new PolicyConfig('native', 'standard', LimitConfig::fromValues(['file.lines' => 900]));
        $config = new LocGuardConfig(
            '/project',
            new ScanConfig(['src'], []),
            ['standard' => $standard, 'native' => $native],
            new ApplyConfig('standard', [new ApplyRuleConfig('native', ['src/Native.php'], 'native')]),
            new ReportConfig('ai', ['path']),
        );

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('matches no scanned PHP files');
        (new FilePolicyAssigner())->assign($config, [
            '/project/src/Example.php' => 'src/Example.php',
        ]);
    }

    public function testAssignRejectsEmptyPhpScan(): void
    {
        $policy = new PolicyConfig('standard', null, LimitConfig::fromValues(['file.lines' => 500]));
        $config = new LocGuardConfig(
            '/project',
            new ScanConfig(['src'], []),
            ['standard' => $policy],
            new ApplyConfig('standard', []),
            new ReportConfig('ai', ['path']),
        );

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('contain no PHP files');
        (new FilePolicyAssigner())->assign($config, []);
    }
}
