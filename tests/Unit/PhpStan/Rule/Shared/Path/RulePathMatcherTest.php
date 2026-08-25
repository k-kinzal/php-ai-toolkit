<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared\Path;

use PhpAiToolkit\PhpStan\Rule\Shared\Path\RulePathMatcher;
use PhpAiToolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\Shared\Path\RulePathMatcher
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer
 */
#[CoversClass(RulePathMatcher::class)]
#[UsesClass(RulePathNormalizer::class)]
final class RulePathMatcherTest extends TestCase
{
    public function testMatchesAcceptsRelativeFilePattern(): void
    {
        $matcher = new RulePathMatcher(['src/App/Boundary.php']);

        self::assertTrue($matcher->matches('/project/src/App/Boundary.php'));
    }

    public function testMatchesAcceptsDirectoryWildcardPattern(): void
    {
        $matcher = new RulePathMatcher(['src/*/Boundary/*']);

        self::assertTrue($matcher->matches('/project/src/App/Boundary/Decoder.php'));
    }

    public function testMatchesRejectsUnmatchedPathsAndAnEmptyConfiguration(): void
    {
        self::assertFalse((new RulePathMatcher(['src/App/Boundary/*']))->matches('/project/src/App/Domain/Order.php'));
        self::assertFalse((new RulePathMatcher())->matches('/project/src/App/Boundary/Decoder.php'));
    }
}
