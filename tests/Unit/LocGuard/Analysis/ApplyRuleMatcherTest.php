<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Analysis\ApplyRuleMatcher;
use Toolkit\LocGuard\Config\Policy\ApplyRuleConfig;
use Toolkit\LocGuard\Filesystem\FilePathPatternMatcher;

/**
 * @covers \Toolkit\LocGuard\Analysis\ApplyRuleMatcher
 */
#[CoversClass(ApplyRuleMatcher::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(FilePathPatternMatcher::class)]
final class ApplyRuleMatcherTest extends TestCase
{
    public function testMatchesReturnsTrueWhenAnyRulePathMatches(): void
    {
        $rule = new ApplyRuleConfig('native', ['src/ZtdPdo.php', 'src/ZtdMysqli.php'], 'native-api');
        $matcher = new ApplyRuleMatcher();

        self::assertTrue($matcher->matches($rule, 'src/ZtdMysqli.php'));
        self::assertFalse($matcher->matches($rule, 'src/Connection.php'));
    }
}
