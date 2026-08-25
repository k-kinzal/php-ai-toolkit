<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Presentation;

use PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueBlockIndenter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueBlockIndenter
 */
#[CoversClass(TestIssueBlockIndenter::class)]
final class TestIssueBlockIndenterTest extends TestCase
{
    public function testIndentPrefixesEveryTrimmedLine(): void
    {
        $indenter = new TestIssueBlockIndenter();

        self::assertSame("  --- Expected\n  +++ Actual\n", $indenter->indent("\n--- Expected\n+++ Actual\n"));
    }
}
