<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Presentation;

use PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueAiFormatter;
use PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueBlockIndenter;
use PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssuePathFormatter;
use PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueSummary;
use PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueTypePresentation;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssue;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueSourceReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueAiFormatter
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\TestIssue
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssuePathFormatter
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\TestIssueSourceReader
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueSummary
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueTypePresentation
 */
#[CoversClass(TestIssueAiFormatter::class)]
#[UsesClass(TestIssue::class)]
#[UsesClass(TestIssuePathFormatter::class)]
#[UsesClass(TestIssueSourceReader::class)]
#[UsesClass(TestIssueSummary::class)]
#[UsesClass(TestIssueTypePresentation::class)]
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
