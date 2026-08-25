<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Analysis\CaseConventionMatcher;
use Toolkit\TreeGuard\Analysis\DirNameInspector;
use Toolkit\TreeGuard\Analysis\Violation;
use Toolkit\TreeGuard\Config\RuleConfig;
use Toolkit\TreeGuard\Filesystem\DirectoryListing;
use Toolkit\TreeGuard\Filesystem\TreeGuardPathResolver;

/**
 * @covers \Toolkit\TreeGuard\Analysis\DirNameInspector
 * @uses \Toolkit\TreeGuard\Analysis\CaseConventionMatcher
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryListing
 * @uses \Toolkit\TreeGuard\Config\RuleConfig
 * @uses \Toolkit\TreeGuard\Filesystem\TreeGuardPathResolver
 * @uses \Toolkit\TreeGuard\Analysis\Violation
 */
#[CoversClass(DirNameInspector::class)]
#[UsesClass(CaseConventionMatcher::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(RuleConfig::class)]
#[UsesClass(TreeGuardPathResolver::class)]
#[UsesClass(Violation::class)]
final class DirNameInspectorTest extends TestCase
{
    public function testInspectPassesMatchingDirs(): void
    {
        $rule = new RuleConfig('src', null, null, null, null, null, null, ['[A-Z]*'], ['Helpers'], null, false, null, 'pascal');
        $listing = new DirectoryListing('src', [], ['Analysis', 'Reporting']);

        self::assertSame([], (new DirNameInspector())->inspect($rule, $listing));
    }

    public function testInspectReportsDeniedDir(): void
    {
        $rule = new RuleConfig('src', null, null, null, null, null, null, null, ['Helpers'], null, false, null, null);
        $listing = new DirectoryListing('src', [], ['Helpers']);

        $violations = (new DirNameInspector())->inspect($rule, $listing);

        self::assertCount(1, $violations);
        self::assertSame('src/Helpers', $violations[0]->path);
        self::assertSame('denied_dir', $violations[0]->rule);
        self::assertSame('Directory "src/Helpers" matches denied pattern "Helpers". Rename or remove it.', $violations[0]->message);
    }

    public function testInspectReportsDisallowedDir(): void
    {
        $rule = new RuleConfig('src', null, null, null, null, null, null, ['[A-Z]*'], null, null, false, null, null);
        $listing = new DirectoryListing('src', [], ['weird']);

        $violations = (new DirNameInspector())->inspect($rule, $listing);

        self::assertCount(1, $violations);
        self::assertSame('disallowed_dir', $violations[0]->rule);
        self::assertSame('Directory "src/weird" does not match any allowed pattern ([A-Z]*). Rename, move, or delete it.', $violations[0]->message);
    }

    public function testInspectReportsEveryDirWhenAllowListIsEmpty(): void
    {
        $rule = new RuleConfig('src', null, null, null, null, null, null, [], null, null, false, null, null);
        $listing = new DirectoryListing('src', [], ['Anything']);

        $violations = (new DirNameInspector())->inspect($rule, $listing);

        self::assertCount(1, $violations);
        self::assertSame('Directory "src/Anything" does not match any allowed pattern (none). Rename, move, or delete it.', $violations[0]->message);
    }

    public function testInspectReportsCaseViolationAndSkipsDotDirs(): void
    {
        $rule = new RuleConfig('src', null, null, null, null, null, null, null, null, null, false, null, 'pascal');
        $listing = new DirectoryListing('src', [], ['.cache', 'helpers']);

        $violations = (new DirNameInspector())->inspect($rule, $listing);

        self::assertCount(1, $violations);
        self::assertSame('src/helpers', $violations[0]->path);
        self::assertSame('dir_case', $violations[0]->rule);
        self::assertSame('Directory name "helpers" in "src" does not follow the pascal convention. Rename it.', $violations[0]->message);
    }

    public function testInspectReportsDeniedDirDirectlyInProjectRoot(): void
    {
        $rule = new RuleConfig('**', null, null, null, null, null, null, null, ['scripts'], null, false, null, null);
        $listing = new DirectoryListing('.', [], ['scripts']);

        $violations = (new DirNameInspector())->inspect($rule, $listing);

        self::assertCount(1, $violations);
        self::assertSame('scripts', $violations[0]->path);
        self::assertSame('Directory "scripts" matches denied pattern "scripts". Rename or remove it.', $violations[0]->message);
    }

    public function testMatchesAnyChecksEachPattern(): void
    {
        self::assertTrue((new DirNameInspector())->matchesAny(['X*', '[A-Z]*'], 'Analysis'));
        self::assertFalse((new DirNameInspector())->matchesAny(['X*'], 'Analysis'));
        self::assertFalse((new DirNameInspector())->matchesAny([], 'Analysis'));
    }
}
