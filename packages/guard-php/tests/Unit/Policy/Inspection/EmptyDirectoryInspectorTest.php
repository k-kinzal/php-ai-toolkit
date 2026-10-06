<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Inspection;

use Guard\Collect\DirectoryListing;
use Guard\Config\Value\DirectoryRuleConfig;
use Guard\Policy\Inspection\EmptyDirectoryInspector;
use Guard\Reporting\DirectoryViolation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Inspection\EmptyDirectoryInspector
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Config\Value\DirectoryRuleConfig
 * @uses \Guard\Reporting\DirectoryViolation
 */
#[CoversClass(EmptyDirectoryInspector::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(DirectoryRuleConfig::class)]
#[UsesClass(DirectoryViolation::class)]
final class EmptyDirectoryInspectorTest extends TestCase
{
    public function testInspectReportsEmptyDirectory(): void
    {
        $rule = new DirectoryRuleConfig('src/**', null, null, null, null, null, null, null, null, null, true, null, null);
        $listing = new DirectoryListing('src/Empty', [], []);

        $violations = (new EmptyDirectoryInspector())->inspect($rule, $listing);

        self::assertCount(1, $violations);
        self::assertSame('src/Empty', $violations[0]->path);
        self::assertSame('empty_directory', $violations[0]->rule);
        self::assertSame('src/**', $violations[0]->pattern);
        self::assertSame('Directory "src/Empty" is empty. Delete it or add its intended contents.', $violations[0]->message);
    }

    public function testInspectPassesWhenDisabled(): void
    {
        $rule = new DirectoryRuleConfig('src/**', null, null, null, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src/Empty', [], []);

        self::assertSame([], (new EmptyDirectoryInspector())->inspect($rule, $listing));
    }

    public function testInspectPassesWhenDirectoryHasEntries(): void
    {
        $rule = new DirectoryRuleConfig('src/**', null, null, null, null, null, null, null, null, null, true, null, null);

        self::assertSame([], (new EmptyDirectoryInspector())->inspect($rule, new DirectoryListing('src/A', ['One.php'], [])));
        self::assertSame([], (new EmptyDirectoryInspector())->inspect($rule, new DirectoryListing('src/B', [], ['Sub'])));
    }
}
