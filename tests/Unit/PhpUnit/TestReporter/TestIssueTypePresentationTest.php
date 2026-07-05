<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use PhpAiToolkit\PhpUnit\TestReporter\TestIssue;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueTypePresentation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestIssueTypePresentation::class)]
final class TestIssueTypePresentationTest extends TestCase
{
    public function testLabelReturnsUppercaseIssueLabel(): void
    {
        $presentation = new TestIssueTypePresentation();

        self::assertSame('FAILED', $presentation->label(TestIssue::TYPE_FAILED));
        self::assertSame('ERROR', $presentation->label(TestIssue::TYPE_ERROR));
        self::assertSame('RISKY', $presentation->label(TestIssue::TYPE_RISKY));
        self::assertSame('SKIPPED', $presentation->label(TestIssue::TYPE_SKIPPED));
    }

    public function testColorReturnsConsoleColorForIssueType(): void
    {
        $presentation = new TestIssueTypePresentation();

        self::assertSame('red', $presentation->color(TestIssue::TYPE_FAILED));
        self::assertSame('red', $presentation->color(TestIssue::TYPE_ERROR));
        self::assertSame('yellow', $presentation->color(TestIssue::TYPE_RISKY));
        self::assertSame('cyan', $presentation->color(TestIssue::TYPE_SKIPPED));
    }
}
