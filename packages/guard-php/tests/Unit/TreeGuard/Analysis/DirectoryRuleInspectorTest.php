<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Analysis\CaseConventionMatcher;
use Toolkit\TreeGuard\Analysis\ChildCountInspector;
use Toolkit\TreeGuard\Analysis\DepthInspector;
use Toolkit\TreeGuard\Analysis\DirectoryRuleInspector;
use Toolkit\TreeGuard\Analysis\DirNameInspector;
use Toolkit\TreeGuard\Analysis\EmptyDirectoryInspector;
use Toolkit\TreeGuard\Analysis\FileNameInspector;
use Toolkit\TreeGuard\Analysis\RequiredFileInspector;
use Toolkit\TreeGuard\Analysis\TotalFileCountInspector;
use Toolkit\TreeGuard\Analysis\Violation;
use Toolkit\TreeGuard\Config\RuleConfig;
use Toolkit\TreeGuard\Filesystem\DirectoryListing;
use Toolkit\TreeGuard\Filesystem\TreeGuardPathResolver;

/**
 * @covers \Toolkit\TreeGuard\Analysis\DirectoryRuleInspector
 * @uses \Toolkit\TreeGuard\Analysis\CaseConventionMatcher
 * @uses \Toolkit\TreeGuard\Analysis\ChildCountInspector
 * @uses \Toolkit\TreeGuard\Analysis\DepthInspector
 * @uses \Toolkit\TreeGuard\Analysis\DirNameInspector
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryListing
 * @uses \Toolkit\TreeGuard\Analysis\EmptyDirectoryInspector
 * @uses \Toolkit\TreeGuard\Analysis\FileNameInspector
 * @uses \Toolkit\TreeGuard\Analysis\RequiredFileInspector
 * @uses \Toolkit\TreeGuard\Config\RuleConfig
 * @uses \Toolkit\TreeGuard\Analysis\TotalFileCountInspector
 * @uses \Toolkit\TreeGuard\Filesystem\TreeGuardPathResolver
 * @uses \Toolkit\TreeGuard\Analysis\Violation
 */
#[CoversClass(DirectoryRuleInspector::class)]
#[UsesClass(CaseConventionMatcher::class)]
#[UsesClass(ChildCountInspector::class)]
#[UsesClass(DepthInspector::class)]
#[UsesClass(DirNameInspector::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(EmptyDirectoryInspector::class)]
#[UsesClass(FileNameInspector::class)]
#[UsesClass(RequiredFileInspector::class)]
#[UsesClass(RuleConfig::class)]
#[UsesClass(TotalFileCountInspector::class)]
#[UsesClass(TreeGuardPathResolver::class)]
#[UsesClass(Violation::class)]
final class DirectoryRuleInspectorTest extends TestCase
{
    public function testInspectMergesViolationsFromEveryConstraint(): void
    {
        $rule = new RuleConfig('src', 1, null, 2, 1, ['*.php'], null, null, null, ['README.md'], false, null, null);
        $listing = new DirectoryListing('src', ['One.php', 'notes.txt'], ['A']);
        $listings = [
            'src' => $listing,
            'src/A' => new DirectoryListing('src/A', ['Two.php'], ['B']),
            'src/A/B' => new DirectoryListing('src/A/B', [], []),
        ];

        $violations = (new DirectoryRuleInspector())->inspect($rule, $listing, $listings);

        self::assertCount(5, $violations);
        self::assertSame('max_files', $violations[0]->rule);
        self::assertSame('max_total_files', $violations[1]->rule);
        self::assertSame('max_depth', $violations[2]->rule);
        self::assertSame('disallowed_file', $violations[3]->rule);
        self::assertSame('missing_required_file', $violations[4]->rule);
    }

    public function testInspectReturnsNoViolationsForCompliantDirectory(): void
    {
        $rule = new RuleConfig('src', 5, 5, 10, 2, ['*.php'], null, null, null, null, true, 'pascal', 'pascal');
        $listing = new DirectoryListing('src', ['One.php'], []);

        self::assertSame([], (new DirectoryRuleInspector())->inspect($rule, $listing, ['src' => $listing]));
    }
}
