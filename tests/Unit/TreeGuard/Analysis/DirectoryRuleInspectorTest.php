<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Analysis;

use PhpAiToolkit\TreeGuard\Analysis\CaseConventionMatcher;
use PhpAiToolkit\TreeGuard\Analysis\ChildCountInspector;
use PhpAiToolkit\TreeGuard\Analysis\DepthInspector;
use PhpAiToolkit\TreeGuard\Analysis\DirectoryRuleInspector;
use PhpAiToolkit\TreeGuard\Analysis\DirNameInspector;
use PhpAiToolkit\TreeGuard\Analysis\EmptyDirectoryInspector;
use PhpAiToolkit\TreeGuard\Analysis\FileNameInspector;
use PhpAiToolkit\TreeGuard\Analysis\RequiredFileInspector;
use PhpAiToolkit\TreeGuard\Analysis\TotalFileCountInspector;
use PhpAiToolkit\TreeGuard\Analysis\Violation;
use PhpAiToolkit\TreeGuard\Config\RuleConfig;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing;
use PhpAiToolkit\TreeGuard\Filesystem\TreeGuardPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
