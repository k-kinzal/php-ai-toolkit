<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Assignment;

use Guard\Collect\Matching\PathPatternMatcher;
use Guard\Config\Assignment\ApplyRuleMatcher;
use Guard\Config\Profile\ApplyRuleConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Assignment\ApplyRuleMatcher
 * @uses \Guard\Collect\Matching\PathPatternMatcher
 * @uses \Guard\Config\Profile\ApplyRuleConfig
 */
#[CoversClass(ApplyRuleMatcher::class)]
#[UsesClass(PathPatternMatcher::class)]
#[UsesClass(ApplyRuleConfig::class)]
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
