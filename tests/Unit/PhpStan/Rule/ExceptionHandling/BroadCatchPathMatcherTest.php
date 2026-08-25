<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ExceptionHandling;

use PhpAiToolkit\PhpStan\Rule\ExceptionHandling\BroadCatchPathMatcher;
use PhpAiToolkit\PhpStan\Rule\Shared\Path\RulePathMatcher;
use PhpAiToolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\ExceptionHandling\BroadCatchPathMatcher
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\Path\RulePathMatcher
 */
#[CoversClass(BroadCatchPathMatcher::class)]
#[UsesClass(RulePathNormalizer::class)]
#[UsesClass(RulePathMatcher::class)]
final class BroadCatchPathMatcherTest extends TestCase
{
    public function testIsAllowedMatchesRelativeFilePattern(): void
    {
        $matcher = new BroadCatchPathMatcher(['src/App/Cli/Application.php']);

        self::assertTrue($matcher->isAllowed('/project/src/App/Cli/Application.php'));
    }

    public function testIsAllowedMatchesDirectoryWildcardPattern(): void
    {
        $matcher = new BroadCatchPathMatcher(['src/*/Cli/*']);

        self::assertTrue($matcher->isAllowed('/project/src/App/Cli/Application.php'));
    }

    public function testIsAllowedRejectsUnmatchedPath(): void
    {
        $matcher = new BroadCatchPathMatcher(['src/App/Cli/*']);

        self::assertFalse($matcher->isAllowed('/project/src/App/Domain/Importer.php'));
    }

    public function testIsAllowedRejectsEverythingWithoutPatterns(): void
    {
        self::assertFalse((new BroadCatchPathMatcher())->isAllowed('/project/src/App/Cli/Application.php'));
    }
}
