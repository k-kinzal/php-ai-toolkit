<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\SourceFileRuleMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SourceFileRuleMatcher::class)]
final class SourceFileRuleMatcherTest extends TestCase
{
    public function testIsSourceFileReturnsTrueForSourceMarker(): void
    {
        self::assertTrue((new SourceFileRuleMatcher())->isSourceFile('/project/src/User.php', '/src/'));
    }
}
