<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Inspection;

use Guard\Collect\DirectoryListing;
use Guard\Config\Value\DirectoryRuleConfig;
use Guard\Policy\Inspection\ChildCountInspector;
use Guard\Reporting\DirectoryViolation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Inspection\ChildCountInspector
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Config\Value\DirectoryRuleConfig
 * @uses \Guard\Reporting\DirectoryViolation
 */
#[CoversClass(ChildCountInspector::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(DirectoryRuleConfig::class)]
#[UsesClass(DirectoryViolation::class)]
final class ChildCountInspectorTest extends TestCase
{
    public function testInspectPassesAtExactLimits(): void
    {
        $rule = new DirectoryRuleConfig('src', 2, 1, null, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src', ['A.php', 'B.php'], ['Sub']);

        self::assertSame([], (new ChildCountInspector())->inspect($rule, $listing));
    }

    public function testInspectReportsFileCountOverLimit(): void
    {
        $rule = new DirectoryRuleConfig('src/*', 1, null, null, null, null, null, null, null, null, false, null, null);
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
        $rule = new DirectoryRuleConfig('src', null, 1, null, null, null, null, null, null, null, false, null, null);
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
        $rule = new DirectoryRuleConfig('src', null, null, null, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src', ['A.php', 'B.php', 'C.php'], ['X', 'Y', 'Z']);

        self::assertSame([], (new ChildCountInspector())->inspect($rule, $listing));
    }
}
