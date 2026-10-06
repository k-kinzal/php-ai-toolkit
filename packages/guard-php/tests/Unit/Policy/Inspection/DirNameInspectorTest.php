<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Inspection;

use Guard\Collect\DirectoryListing;
use Guard\Collect\Filesystem\Path as PathResolver;
use Guard\Config\Value\DirectoryRuleConfig;
use Guard\Policy\Inspection\CaseConventionMatcher;
use Guard\Policy\Inspection\DirNameInspector;
use Guard\Reporting\DirectoryViolation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Inspection\DirNameInspector
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\Filesystem\Path
 * @uses \Guard\Config\Value\DirectoryRuleConfig
 * @uses \Guard\Policy\Inspection\CaseConventionMatcher
 * @uses \Guard\Reporting\DirectoryViolation
 */
#[CoversClass(DirNameInspector::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(PathResolver::class)]
#[UsesClass(DirectoryRuleConfig::class)]
#[UsesClass(CaseConventionMatcher::class)]
#[UsesClass(DirectoryViolation::class)]
final class DirNameInspectorTest extends TestCase
{
    public function testInspectPassesMatchingDirs(): void
    {
        $rule = new DirectoryRuleConfig('src', null, null, null, null, null, null, ['[A-Z]*'], ['Helpers'], null, false, null, 'pascal');
        $listing = new DirectoryListing('src', [], ['Analysis', 'Reporting']);

        self::assertSame([], (new DirNameInspector())->inspect($rule, $listing));
    }

    public function testInspectReportsDeniedDir(): void
    {
        $rule = new DirectoryRuleConfig('src', null, null, null, null, null, null, null, ['Helpers'], null, false, null, null);
        $listing = new DirectoryListing('src', [], ['Helpers']);

        $violations = (new DirNameInspector())->inspect($rule, $listing);

        self::assertCount(1, $violations);
        self::assertSame('src/Helpers', $violations[0]->path);
        self::assertSame('denied_dir', $violations[0]->rule);
        self::assertSame('Directory "src/Helpers" matches denied pattern "Helpers". Rename or remove it.', $violations[0]->message);
    }

    public function testInspectReportsDisallowedDir(): void
    {
        $rule = new DirectoryRuleConfig('src', null, null, null, null, null, null, ['[A-Z]*'], null, null, false, null, null);
        $listing = new DirectoryListing('src', [], ['weird']);

        $violations = (new DirNameInspector())->inspect($rule, $listing);

        self::assertCount(1, $violations);
        self::assertSame('disallowed_dir', $violations[0]->rule);
        self::assertSame('Directory "src/weird" does not match any allowed pattern ([A-Z]*). Rename, move, or delete it.', $violations[0]->message);
    }

    public function testInspectReportsEveryDirWhenAllowListIsEmpty(): void
    {
        $rule = new DirectoryRuleConfig('src', null, null, null, null, null, null, [], null, null, false, null, null);
        $listing = new DirectoryListing('src', [], ['Anything']);

        $violations = (new DirNameInspector())->inspect($rule, $listing);

        self::assertCount(1, $violations);
        self::assertSame('Directory "src/Anything" does not match any allowed pattern (none). Rename, move, or delete it.', $violations[0]->message);
    }

    public function testInspectReportsCaseViolationAndSkipsDotDirs(): void
    {
        $rule = new DirectoryRuleConfig('src', null, null, null, null, null, null, null, null, null, false, null, 'pascal');
        $listing = new DirectoryListing('src', [], ['.cache', 'helpers']);

        $violations = (new DirNameInspector())->inspect($rule, $listing);

        self::assertCount(1, $violations);
        self::assertSame('src/helpers', $violations[0]->path);
        self::assertSame('dir_case', $violations[0]->rule);
        self::assertSame('Directory name "helpers" in "src" does not follow the pascal convention. Rename it.', $violations[0]->message);
    }

    public function testInspectReportsDeniedDirDirectlyInProjectRoot(): void
    {
        $rule = new DirectoryRuleConfig('**', null, null, null, null, null, null, null, ['scripts'], null, false, null, null);
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
