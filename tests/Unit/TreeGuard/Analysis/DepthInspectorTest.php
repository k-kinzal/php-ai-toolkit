<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Analysis\DepthInspector;
use Toolkit\TreeGuard\Analysis\Violation;
use Toolkit\TreeGuard\Config\RuleConfig;
use Toolkit\TreeGuard\Filesystem\DirectoryListing;
use Toolkit\TreeGuard\Filesystem\TreeGuardPathResolver;

/**
 * @covers \Toolkit\TreeGuard\Analysis\DepthInspector
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryListing
 * @uses \Toolkit\TreeGuard\Config\RuleConfig
 * @uses \Toolkit\TreeGuard\Filesystem\TreeGuardPathResolver
 * @uses \Toolkit\TreeGuard\Analysis\Violation
 */
#[CoversClass(DepthInspector::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(RuleConfig::class)]
#[UsesClass(TreeGuardPathResolver::class)]
#[UsesClass(Violation::class)]
final class DepthInspectorTest extends TestCase
{
    public function testInspectSkipsAbsentLimit(): void
    {
        $rule = new RuleConfig('src', null, null, null, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src', [], ['A']);
        $listings = [
            'src' => $listing,
            'src/A' => new DirectoryListing('src/A', [], []),
        ];

        self::assertSame([], (new DepthInspector())->inspect($rule, $listing, $listings));
    }

    public function testInspectPassesAtExactLimit(): void
    {
        $rule = new RuleConfig('src', null, null, null, 2, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src', [], ['A']);
        $listings = [
            'src' => $listing,
            'src/A' => new DirectoryListing('src/A', [], ['B']),
            'src/A/B' => new DirectoryListing('src/A/B', [], []),
        ];

        self::assertSame([], (new DepthInspector())->inspect($rule, $listing, $listings));
    }

    public function testInspectReportsEachDescendantOverLimit(): void
    {
        $rule = new RuleConfig('src', null, null, null, 1, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src', [], ['A']);
        $listings = [
            'src' => $listing,
            'src/A' => new DirectoryListing('src/A', [], ['B']),
            'src/A/B' => new DirectoryListing('src/A/B', [], ['C']),
            'src/A/B/C' => new DirectoryListing('src/A/B/C', [], []),
        ];

        $violations = (new DepthInspector())->inspect($rule, $listing, $listings);

        self::assertCount(2, $violations);
        self::assertSame('src/A/B', $violations[0]->path);
        self::assertSame('max_depth', $violations[0]->rule);
        self::assertSame(2, $violations[0]->actual);
        self::assertSame(1, $violations[0]->limit);
        self::assertSame('Directory "src/A/B" is nested 2 levels below "src" but the limit is 1. Flatten the directory structure.', $violations[0]->message);
        self::assertSame('src/A/B/C', $violations[1]->path);
        self::assertSame(3, $violations[1]->actual);
    }

    public function testInspectCountsDepthFromProjectRoot(): void
    {
        $rule = new RuleConfig('.', null, null, null, 1, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('.', [], ['src']);
        $listings = [
            '.' => $listing,
            'src' => new DirectoryListing('src', [], ['A']),
            'src/A' => new DirectoryListing('src/A', [], []),
        ];

        $violations = (new DepthInspector())->inspect($rule, $listing, $listings);

        self::assertCount(1, $violations);
        self::assertSame('src/A', $violations[0]->path);
        self::assertSame(2, $violations[0]->actual);
    }
}
