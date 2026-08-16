<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Analysis;

use PhpAiToolkit\TreeGuard\Analysis\CaseConventionMatcher;
use PhpAiToolkit\TreeGuard\Analysis\FileNameInspector;
use PhpAiToolkit\TreeGuard\Analysis\Violation;
use PhpAiToolkit\TreeGuard\Config\RuleConfig;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing;
use PhpAiToolkit\TreeGuard\Filesystem\TreeGuardPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileNameInspector::class)]
#[UsesClass(CaseConventionMatcher::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(RuleConfig::class)]
#[UsesClass(TreeGuardPathResolver::class)]
#[UsesClass(Violation::class)]
final class FileNameInspectorTest extends TestCase
{
    public function testInspectPassesMatchingFiles(): void
    {
        $rule = new RuleConfig('src/**', null, null, null, null, ['*.php'], ['*Helper.php'], null, null, null, false, 'pascal', null);
        $listing = new DirectoryListing('src', ['AiReporter.php'], []);

        self::assertSame([], (new FileNameInspector())->inspect($rule, $listing));
    }

    public function testInspectReportsDeniedFile(): void
    {
        $rule = new RuleConfig('src/**', null, null, null, null, null, ['*Helper.php'], null, null, null, false, null, null);
        $listing = new DirectoryListing('src/A', ['BarHelper.php'], []);

        $violations = (new FileNameInspector())->inspect($rule, $listing);

        self::assertCount(1, $violations);
        self::assertSame('src/A/BarHelper.php', $violations[0]->path);
        self::assertSame('denied_file', $violations[0]->rule);
        self::assertSame('src/**', $violations[0]->pattern);
        self::assertNull($violations[0]->actual);
        self::assertSame('File "src/A/BarHelper.php" matches denied pattern "*Helper.php". Rename or remove it.', $violations[0]->message);
    }

    public function testInspectReportsDisallowedFile(): void
    {
        $rule = new RuleConfig('src/**', null, null, null, null, ['*.php'], null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src', ['notes.txt'], []);

        $violations = (new FileNameInspector())->inspect($rule, $listing);

        self::assertCount(1, $violations);
        self::assertSame('disallowed_file', $violations[0]->rule);
        self::assertSame('File "src/notes.txt" does not match any allowed pattern (*.php). Rename, move, or delete it.', $violations[0]->message);
    }

    public function testInspectReportsEveryFileWhenAllowListIsEmpty(): void
    {
        $rule = new RuleConfig('src', null, null, null, null, [], null, null, null, null, false, null, null);
        $listing = new DirectoryListing('src', ['Anything.php'], []);

        $violations = (new FileNameInspector())->inspect($rule, $listing);

        self::assertCount(1, $violations);
        self::assertSame('disallowed_file', $violations[0]->rule);
        self::assertSame('File "src/Anything.php" does not match any allowed pattern (none). Rename, move, or delete it.', $violations[0]->message);
    }

    public function testInspectReportsCaseViolationAndSkipsDotfiles(): void
    {
        $rule = new RuleConfig('src/**', null, null, null, null, null, null, null, null, null, false, 'pascal', null);
        $listing = new DirectoryListing('src/Reporting', ['.gitkeep', 'my_reporter.php'], []);

        $violations = (new FileNameInspector())->inspect($rule, $listing);

        self::assertCount(1, $violations);
        self::assertSame('src/Reporting/my_reporter.php', $violations[0]->path);
        self::assertSame('file_case', $violations[0]->rule);
        self::assertSame('File name "my_reporter.php" in "src/Reporting" does not follow the pascal convention. Rename it.', $violations[0]->message);
    }

    public function testInspectReportsIndependentViolationsForOneFile(): void
    {
        $rule = new RuleConfig('src/**', null, null, null, null, ['*.php'], ['bad_*'], null, null, null, false, 'pascal', null);
        $listing = new DirectoryListing('src', ['bad_name.txt'], []);

        $violations = (new FileNameInspector())->inspect($rule, $listing);

        self::assertCount(3, $violations);
        self::assertSame('denied_file', $violations[0]->rule);
        self::assertSame('disallowed_file', $violations[1]->rule);
        self::assertSame('file_case', $violations[2]->rule);
    }

    public function testMatchesAnyChecksEachPattern(): void
    {
        self::assertTrue((new FileNameInspector())->matchesAny(['*.txt', '*.php'], 'A.php'));
        self::assertFalse((new FileNameInspector())->matchesAny(['*.txt'], 'A.php'));
        self::assertFalse((new FileNameInspector())->matchesAny([], 'A.php'));
    }
}
