<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Analysis;

use PhpAiToolkit\TreeGuard\Analysis\EmptyDirectoryInspector;
use PhpAiToolkit\TreeGuard\Analysis\Violation;
use PhpAiToolkit\TreeGuard\Config\RuleConfig;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmptyDirectoryInspector::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(RuleConfig::class)]
#[UsesClass(Violation::class)]
final class EmptyDirectoryInspectorTest extends TestCase
{
    public function testInspectReportsEmptyDirectory(): void
    {
        $rule = new RuleConfig('src/**', null, null, null, null, null, null, null, null, null, true, null, null);
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
        $rule = new RuleConfig('src/**', null, null, null, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src/Empty', [], []);

        self::assertSame([], (new EmptyDirectoryInspector())->inspect($rule, $listing));
    }

    public function testInspectPassesWhenDirectoryHasEntries(): void
    {
        $rule = new RuleConfig('src/**', null, null, null, null, null, null, null, null, null, true, null, null);

        self::assertSame([], (new EmptyDirectoryInspector())->inspect($rule, new DirectoryListing('src/A', ['One.php'], [])));
        self::assertSame([], (new EmptyDirectoryInspector())->inspect($rule, new DirectoryListing('src/B', [], ['Sub'])));
    }
}
