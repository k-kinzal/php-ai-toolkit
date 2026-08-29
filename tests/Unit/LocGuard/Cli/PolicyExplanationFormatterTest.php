<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Analysis\FilePolicyAssignment;
use Toolkit\LocGuard\Cli\PolicyExplanationFormatter;
use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\Config\Policy\PolicyConfig;

#[CoversClass(PolicyExplanationFormatter::class)]
#[UsesClass(FilePolicyAssignment::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(PolicyConfig::class)]
final class PolicyExplanationFormatterTest extends TestCase
{
    public function testFormatShowsRulePolicyInheritanceAndEffectiveLimits(): void
    {
        $policy = new PolicyConfig('native-api', 'standard', LimitConfig::fromValues(['file.lines' => 900]));
        $assignment = new FilePolicyAssignment('/project/src/Native.php', 'src/Native.php', $policy, 'native');
        $output = (new PolicyExplanationFormatter())->format($assignment);

        self::assertStringContainsString('Matched rule: native', $output);
        self::assertStringContainsString('Policy: native-api', $output);
        self::assertStringContainsString('file.lines: 900', $output);
        self::assertStringContainsString('method.lines: disabled', $output);
    }
}
