<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use PhpAiToolkit\PhpUnit\TestReporter\TestIssue;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueAiFormatter;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueBlockIndenter;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssuePathFormatter;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueSourceReader;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueSummary;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueTypePresentation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestIssueAiFormatter::class)]
final class TestIssueAiFormatterTest extends TestCase
{
    public function testFormatUsesPathLineIssueBlocks(): void
    {
        $formatter = new TestIssueAiFormatter(
            new TestIssuePathFormatter('/project'),
            new TestIssueSourceReader(),
            new TestIssueTypePresentation(),
            new TestIssueSummary(),
            new TestIssueBlockIndenter(),
        );

        $output = $formatter->format([
            new TestIssue(TestIssue::TYPE_FAILED, 'T::m', 'FooTest::testBar', '/project/tests/FooTest.php', 42, 'Some failure'),
        ]);

        self::assertStringStartsWith('--- PHPUnit: 1 failure ---', $output);
        self::assertStringContainsString('tests/FooTest.php:42 [FAILED]', $output);
        self::assertStringContainsString('FooTest::testBar', $output);
    }
}
