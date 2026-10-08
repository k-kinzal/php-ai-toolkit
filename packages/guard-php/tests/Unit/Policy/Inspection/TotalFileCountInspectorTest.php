<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Inspection;

use Guard\Collect\DirectoryListing;
use Guard\Collect\Filesystem\Path as PathResolver;
use Guard\Config\Value\DirectoryRuleConfig;
use Guard\Policy\Inspection\TotalFileCountInspector;
use Guard\Reporting\DirectoryViolation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Inspection\TotalFileCountInspector
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\Filesystem\Path
 * @uses \Guard\Config\Value\DirectoryRuleConfig
 * @uses \Guard\Reporting\DirectoryViolation
 */
#[CoversClass(TotalFileCountInspector::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(PathResolver::class)]
#[UsesClass(DirectoryRuleConfig::class)]
#[UsesClass(DirectoryViolation::class)]
final class TotalFileCountInspectorTest extends TestCase
{
    public function testInspectSkipsAbsentLimit(): void
    {
        $rule = new DirectoryRuleConfig('src', null, null, null, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src', ['A.php'], []);

        self::assertSame([], (new TotalFileCountInspector())->inspect($rule, $listing, ['src' => $listing]));
    }

    public function testInspectPassesAtExactLimit(): void
    {
        $rule = new DirectoryRuleConfig('src', null, null, 3, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src', ['Root.php'], ['A']);
        $listings = [
            'src' => $listing,
            'src/A' => new DirectoryListing('src/A', ['One.php', 'Two.php'], []),
        ];

        self::assertSame([], (new TotalFileCountInspector())->inspect($rule, $listing, $listings));
    }

    public function testInspectReportsSubtreeTotalOverLimit(): void
    {
        $rule = new DirectoryRuleConfig('src', null, null, 2, null, null, null, null, null, null, false, null, null);
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
        self::assertSame('Directory "src" contains 3 files in total but the limit is 2. Move independent groups outside this subtree or merge related files until the total meets the limit; adding subdirectories inside it does not reduce the total.', $violations[0]->message);
    }

    public function testInspectCountsWholeProjectFromRootOnce(): void
    {
        $rule = new DirectoryRuleConfig('.', null, null, 2, null, null, null, null, null, null, false, null, null);
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
        $rule = new DirectoryRuleConfig('src/Ab', null, null, 1, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src/Ab', ['One.php'], []);
        $listings = [
            'src/Ab' => $listing,
            'src/Abc' => new DirectoryListing('src/Abc', ['Two.php', 'Three.php'], []),
        ];

        self::assertSame([], (new TotalFileCountInspector())->inspect($rule, $listing, $listings));
    }
}
