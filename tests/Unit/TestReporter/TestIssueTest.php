<?php

declare(strict_types=1);

namespace Tests\Unit\TestReporter;

use PhpStanAiRules\TestReporter\TestIssue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestIssue::class)]
final class TestIssueTest extends TestCase
{
    public function testAllPropertiesAccessibleAfterCreation(): void
    {
        $issue = new TestIssue(
            TestIssue::TYPE_FAILED,
            'Tests\Unit\FooTest::testBar',
            'FooTest::testBar',
            '/path/to/tests/Unit/FooTest.php',
            42,
            'Failed asserting that false is true.',
            "--- Expected\n+++ Actual\n-true\n+false",
            '/path/to/src/Foo.php',
            28,
        );

        self::assertSame(TestIssue::TYPE_FAILED, $issue->type);
        self::assertSame('Tests\Unit\FooTest::testBar', $issue->testId);
        self::assertSame('FooTest::testBar', $issue->testName);
        self::assertSame('/path/to/tests/Unit/FooTest.php', $issue->testFile);
        self::assertSame(42, $issue->testLine);
        self::assertSame('Failed asserting that false is true.', $issue->message);
        self::assertSame("--- Expected\n+++ Actual\n-true\n+false", $issue->diff);
        self::assertSame('/path/to/src/Foo.php', $issue->sourceFile);
        self::assertSame(28, $issue->sourceLine);
    }

    public function testOptionalPropertiesDefaultToNull(): void
    {
        $issue = new TestIssue(
            TestIssue::TYPE_RISKY,
            'Tests\Unit\BarTest::testBaz',
            'BarTest::testBaz',
            '/path/to/tests/Unit/BarTest.php',
            10,
            'This test did not perform any assertions',
        );

        self::assertSame(TestIssue::TYPE_RISKY, $issue->type);
        self::assertNull($issue->diff);
        self::assertNull($issue->sourceFile);
        self::assertNull($issue->sourceLine);
    }

    public function testTypeConstantsHaveExpectedValues(): void
    {
        self::assertSame('failed', TestIssue::TYPE_FAILED);
        self::assertSame('error', TestIssue::TYPE_ERROR);
        self::assertSame('risky', TestIssue::TYPE_RISKY);
        self::assertSame('skipped', TestIssue::TYPE_SKIPPED);
    }
}
