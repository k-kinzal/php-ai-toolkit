<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use PhpAiToolkit\PhpUnit\TestReporter\TestIssue;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueInput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestIssueInput::class)]
final class TestIssueInputTest extends TestCase
{
    public function testAllPropertiesAccessibleAfterCreation(): void
    {
        $input = new TestIssueInput(
            TestIssue::TYPE_FAILED,
            'Tests\FooTest::testBar',
            'FooTest::testBar',
            '/tmp/FooTest.php',
            42,
            'Failed',
            'diff',
            '/tmp/FooTest.php:42',
        );

        self::assertSame(TestIssue::TYPE_FAILED, $input->type);
        self::assertSame('Tests\FooTest::testBar', $input->testId);
        self::assertSame('FooTest::testBar', $input->testName);
        self::assertSame('/tmp/FooTest.php', $input->testFile);
        self::assertSame(42, $input->testLine);
        self::assertSame('Failed', $input->message);
        self::assertSame('diff', $input->diff);
        self::assertSame('/tmp/FooTest.php:42', $input->stackTrace);
    }

    public function testOptionalPropertiesDefaultToNullAndEmptyTrace(): void
    {
        $input = new TestIssueInput(
            TestIssue::TYPE_RISKY,
            'Tests\FooTest::testRisky',
            'FooTest::testRisky',
            '/tmp/FooTest.php',
            10,
            'Risky',
        );

        self::assertNull($input->diff);
        self::assertSame('', $input->stackTrace);
    }
}
