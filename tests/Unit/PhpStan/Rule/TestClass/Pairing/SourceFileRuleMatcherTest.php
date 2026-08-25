<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass\Pairing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\TestClass\Pairing\SourceFileRuleMatcher;

/**
 * @covers \Toolkit\PhpStan\Rule\TestClass\Pairing\SourceFileRuleMatcher
 */
#[CoversClass(SourceFileRuleMatcher::class)]
final class SourceFileRuleMatcherTest extends TestCase
{
    public function testIsSourceFileReturnsTrueForSourceMarker(): void
    {
        self::assertTrue((new SourceFileRuleMatcher())->isSourceFile('/project/src/User.php', '/src/'));
    }
}
