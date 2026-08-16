<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Analysis;

use PhpAiToolkit\TreeGuard\Analysis\TotalFileCountInspector;
use PhpAiToolkit\TreeGuard\Analysis\Violation;
use PhpAiToolkit\TreeGuard\Config\RuleConfig;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing;
use PhpAiToolkit\TreeGuard\Filesystem\TreeGuardPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TotalFileCountInspector::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(RuleConfig::class)]
#[UsesClass(TreeGuardPathResolver::class)]
#[UsesClass(Violation::class)]
final class TotalFileCountInspectorTest extends TestCase
{
    public function testInspectSkipsAbsentLimit(): void
    {
        $rule = new RuleConfig('src', null, null, null, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src', ['A.php'], []);

        self::assertSame([], (new TotalFileCountInspector())->inspect($rule, $listing, ['src' => $listing]));
    }

    public function testInspectPassesAtExactLimit(): void
    {
        $rule = new RuleConfig('src', null, null, 3, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src', ['Root.php'], ['A']);
        $listings = [
            'src' => $listing,
            'src/A' => new DirectoryListing('src/A', ['One.php', 'Two.php'], []),
        ];

        self::assertSame([], (new TotalFileCountInspector())->inspect($rule, $listing, $listings));
    }

    public function testInspectReportsSubtreeTotalOverLimit(): void
    {
        $rule = new RuleConfig('src', null, null, 2, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src', ['Root.php'], ['A']);
        $listings = [
            'src' => $listing,
            'src/A' => new DirectoryListing('src/A', ['One.php', 'Two.php'], []),
        ];

        $violations = (new TotalFileCountInspector())->inspect($rule, $listing, $listings);

        self::assertCount(1, $violations);
        self::assertSame('src', $violations[0]->path);
        self::assertSame('max_total_files', $violations[0]->rule);
        self::assertSame(3, $violations[0]->actual);
        self::assertSame(2, $violations[0]->limit);
        self::assertSame('Directory "src" contains 3 files in total but the limit is 2. Restructure or split the subtree.', $violations[0]->message);
    }

    public function testInspectCountsWholeProjectFromRootOnce(): void
    {
        $rule = new RuleConfig('.', null, null, 2, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('.', ['composer.json'], ['src']);
        $listings = [
            '.' => $listing,
            'src' => new DirectoryListing('src', ['One.php', 'Two.php'], []),
        ];

        $violations = (new TotalFileCountInspector())->inspect($rule, $listing, $listings);

        self::assertCount(1, $violations);
        self::assertSame('.', $violations[0]->path);
        self::assertSame(3, $violations[0]->actual);
    }

    public function testInspectIgnoresSiblingsSharingNamePrefix(): void
    {
        $rule = new RuleConfig('src/Ab', null, null, 1, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src/Ab', ['One.php'], []);
        $listings = [
            'src/Ab' => $listing,
            'src/Abc' => new DirectoryListing('src/Abc', ['Two.php', 'Three.php'], []),
        ];

        self::assertSame([], (new TotalFileCountInspector())->inspect($rule, $listing, $listings));
    }
}
