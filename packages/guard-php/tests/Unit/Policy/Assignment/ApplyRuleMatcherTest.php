<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Assignment;

use Guard\Input\PathPatternMatcher;
use Guard\Policy\Assignment\ApplyRuleMatcher;
use Guard\Policy\Definition\ApplyRuleConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Assignment\ApplyRuleMatcher
 * @uses \Guard\Input\PathPatternMatcher
 * @uses \Guard\Policy\Definition\ApplyRuleConfig
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
