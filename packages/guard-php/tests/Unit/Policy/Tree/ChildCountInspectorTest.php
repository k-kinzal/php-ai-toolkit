<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Tree;

use Guard\Collect\Tree\Filesystem\DirectoryListing;
use Guard\Config\Tree\RuleConfig;
use Guard\Policy\Tree\ChildCountInspector;
use Guard\Policy\Tree\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Tree\ChildCountInspector
 * @uses \Guard\Collect\Tree\Filesystem\DirectoryListing
 * @uses \Guard\Config\Tree\RuleConfig
 * @uses \Guard\Policy\Tree\Violation
 */
#[CoversClass(ChildCountInspector::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(RuleConfig::class)]
#[UsesClass(Violation::class)]
final class ChildCountInspectorTest extends TestCase
{
    public function testInspectPassesAtExactLimits(): void
    {
        $rule = new RuleConfig('src', 2, 1, null, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src', ['A.php', 'B.php'], ['Sub']);

        self::assertSame([], (new ChildCountInspector())->inspect($rule, $listing));
    }

    public function testInspectReportsFileCountOverLimit(): void
    {
        $rule = new RuleConfig('src/*', 1, null, null, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src/A', ['One.php', 'Two.php'], []);

        $violations = (new ChildCountInspector())->inspect($rule, $listing);

        self::assertCount(1, $violations);
        self::assertSame('src/A', $violations[0]->path);
        self::assertSame('max_files', $violations[0]->rule);
        self::assertSame('src/*', $violations[0]->pattern);
        self::assertSame(2, $violations[0]->actual);
        self::assertSame(1, $violations[0]->limit);
        self::assertSame('Directory "src/A" contains 2 files but the limit is 1. Move or merge files until at most 1 remain.', $violations[0]->message);
    }

    public function testInspectReportsDirCountOverLimit(): void
    {
        $rule = new RuleConfig('src', null, 1, null, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src', [], ['A', 'B']);

        $violations = (new ChildCountInspector())->inspect($rule, $listing);

        self::assertCount(1, $violations);
        self::assertSame('max_dirs', $violations[0]->rule);
        self::assertSame(2, $violations[0]->actual);
        self::assertSame(1, $violations[0]->limit);
        self::assertSame('Directory "src" contains 2 subdirectories but the limit is 1. Merge or flatten subdirectories.', $violations[0]->message);
    }

    public function testInspectSkipsAbsentLimits(): void
    {
        $rule = new RuleConfig('src', null, null, null, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src', ['A.php', 'B.php', 'C.php'], ['X', 'Y', 'Z']);

        self::assertSame([], (new ChildCountInspector())->inspect($rule, $listing));
    }
}
