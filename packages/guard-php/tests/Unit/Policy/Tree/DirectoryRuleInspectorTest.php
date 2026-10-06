<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Tree;

use Guard\Collect\Tree\Filesystem\DirectoryListing;
use Guard\Collect\Tree\Filesystem\PathResolver;
use Guard\Config\Tree\RuleConfig;
use Guard\Policy\Tree\CaseConventionMatcher;
use Guard\Policy\Tree\ChildCountInspector;
use Guard\Policy\Tree\DepthInspector;
use Guard\Policy\Tree\DirectoryRuleInspector;
use Guard\Policy\Tree\DirNameInspector;
use Guard\Policy\Tree\EmptyDirectoryInspector;
use Guard\Policy\Tree\FileNameInspector;
use Guard\Policy\Tree\RequiredFileInspector;
use Guard\Policy\Tree\TotalFileCountInspector;
use Guard\Policy\Tree\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Tree\DirectoryRuleInspector
 * @uses \Guard\Collect\Tree\Filesystem\DirectoryListing
 * @uses \Guard\Collect\Tree\Filesystem\PathResolver
 * @uses \Guard\Config\Tree\RuleConfig
 * @uses \Guard\Policy\Tree\CaseConventionMatcher
 * @uses \Guard\Policy\Tree\ChildCountInspector
 * @uses \Guard\Policy\Tree\DepthInspector
 * @uses \Guard\Policy\Tree\DirNameInspector
 * @uses \Guard\Policy\Tree\EmptyDirectoryInspector
 * @uses \Guard\Policy\Tree\FileNameInspector
 * @uses \Guard\Policy\Tree\RequiredFileInspector
 * @uses \Guard\Policy\Tree\TotalFileCountInspector
 * @uses \Guard\Policy\Tree\Violation
 */
#[CoversClass(DirectoryRuleInspector::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(PathResolver::class)]
#[UsesClass(RuleConfig::class)]
#[UsesClass(CaseConventionMatcher::class)]
#[UsesClass(ChildCountInspector::class)]
#[UsesClass(DepthInspector::class)]
#[UsesClass(DirNameInspector::class)]
#[UsesClass(EmptyDirectoryInspector::class)]
#[UsesClass(FileNameInspector::class)]
#[UsesClass(RequiredFileInspector::class)]
#[UsesClass(TotalFileCountInspector::class)]
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
