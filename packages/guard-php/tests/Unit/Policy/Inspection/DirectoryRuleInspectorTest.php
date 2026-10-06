<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Inspection;

use Guard\Collect\DirectoryListing;
use Guard\Collect\Filesystem\Path as PathResolver;
use Guard\Config\Value\DirectoryRuleConfig;
use Guard\Policy\Inspection\CaseConventionMatcher;
use Guard\Policy\Inspection\ChildCountInspector;
use Guard\Policy\Inspection\DepthInspector;
use Guard\Policy\Inspection\DirectoryRuleInspector;
use Guard\Policy\Inspection\DirNameInspector;
use Guard\Policy\Inspection\EmptyDirectoryInspector;
use Guard\Policy\Inspection\FileNameInspector;
use Guard\Policy\Inspection\RequiredFileInspector;
use Guard\Policy\Inspection\TotalFileCountInspector;
use Guard\Reporting\DirectoryViolation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Inspection\DirectoryRuleInspector
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\Filesystem\Path
 * @uses \Guard\Config\Value\DirectoryRuleConfig
 * @uses \Guard\Policy\Inspection\CaseConventionMatcher
 * @uses \Guard\Policy\Inspection\ChildCountInspector
 * @uses \Guard\Policy\Inspection\DepthInspector
 * @uses \Guard\Policy\Inspection\DirNameInspector
 * @uses \Guard\Policy\Inspection\EmptyDirectoryInspector
 * @uses \Guard\Policy\Inspection\FileNameInspector
 * @uses \Guard\Policy\Inspection\RequiredFileInspector
 * @uses \Guard\Policy\Inspection\TotalFileCountInspector
 * @uses \Guard\Reporting\DirectoryViolation
 */
#[CoversClass(DirectoryRuleInspector::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(PathResolver::class)]
#[UsesClass(DirectoryRuleConfig::class)]
#[UsesClass(CaseConventionMatcher::class)]
#[UsesClass(ChildCountInspector::class)]
#[UsesClass(DepthInspector::class)]
#[UsesClass(DirNameInspector::class)]
#[UsesClass(EmptyDirectoryInspector::class)]
#[UsesClass(FileNameInspector::class)]
#[UsesClass(RequiredFileInspector::class)]
#[UsesClass(TotalFileCountInspector::class)]
#[UsesClass(DirectoryViolation::class)]
final class DirectoryRuleInspectorTest extends TestCase
{
    public function testInspectMergesViolationsFromEveryConstraint(): void
    {
        $rule = new DirectoryRuleConfig('src', 1, null, 2, 1, ['*.php'], null, null, null, ['README.md'], false, null, null);
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
        $rule = new DirectoryRuleConfig('src', 5, 5, 10, 2, ['*.php'], null, null, null, null, true, 'pascal', 'pascal');
        $listing = new DirectoryListing('src', ['One.php'], []);

        self::assertSame([], (new DirectoryRuleInspector())->inspect($rule, $listing, ['src' => $listing]));
    }
}
