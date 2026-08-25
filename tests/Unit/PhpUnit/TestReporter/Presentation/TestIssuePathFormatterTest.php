<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Presentation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssuePathFormatter;

/**
 * @covers \Toolkit\PhpUnit\TestReporter\Presentation\TestIssuePathFormatter
 */
#[CoversClass(TestIssuePathFormatter::class)]
final class TestIssuePathFormatterTest extends TestCase
{
    public function testRelativeReturnsPathBelowBaseDirectory(): void
    {
        $formatter = new TestIssuePathFormatter('/project');

        self::assertSame('tests/FooTest.php', $formatter->relative('/project/tests/FooTest.php'));
    }

    public function testRelativeReturnsOriginalPathOutsideBaseDirectory(): void
    {
        $formatter = new TestIssuePathFormatter('/project');

        self::assertSame('/tmp/FooTest.php', $formatter->relative('/tmp/FooTest.php'));
    }
}
