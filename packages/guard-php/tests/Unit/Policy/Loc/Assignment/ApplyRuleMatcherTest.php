<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Loc\Assignment;

use Guard\Collect\Php\Filesystem\FilePathPatternMatcher;
use Guard\Config\Loc\Policy\ApplyRuleConfig;
use Guard\Policy\Loc\Assignment\ApplyRuleMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Loc\Assignment\ApplyRuleMatcher
 * @uses \Guard\Collect\Php\Filesystem\FilePathPatternMatcher
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfig
 */
#[CoversClass(ApplyRuleMatcher::class)]
#[UsesClass(FilePathPatternMatcher::class)]
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
