<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Presentation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssueAiFormatter;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssueBlockIndenter;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssuePathFormatter;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssueSummary;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssueTypePresentation;
use Toolkit\PhpUnit\TestReporter\TestIssue;
use Toolkit\PhpUnit\TestReporter\TestIssueSourceReader;

/**
 * @covers \Toolkit\PhpUnit\TestReporter\Presentation\TestIssueAiFormatter
 * @uses \Toolkit\PhpUnit\TestReporter\TestIssue
 * @uses \Toolkit\PhpUnit\TestReporter\Presentation\TestIssuePathFormatter
 * @uses \Toolkit\PhpUnit\TestReporter\TestIssueSourceReader
 * @uses \Toolkit\PhpUnit\TestReporter\Presentation\TestIssueSummary
 * @uses \Toolkit\PhpUnit\TestReporter\Presentation\TestIssueTypePresentation
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
