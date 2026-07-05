<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Legacy;

use Override;
use PhpAiToolkit\PhpUnit\TestReporter\Legacy\LegacyTestIssueFactory;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssue;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SebastianBergmann\Comparator\ComparisonFailure;

#[CoversNothing]
final class LegacyTestIssueFactoryTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        if (!interface_exists('PHPUnit\Framework\TestListener')) {
            self::markTestSkipped('Requires PHPUnit 9 legacy TestListener API.');
        }
    }

    public function testFromFailureConvertsLegacyFailureToInput(): void
    {
        $factory = new LegacyTestIssueFactory();
        $failure = new ExpectationFailedException(
            'Failed asserting that false is true.',
            new ComparisonFailure(true, false, 'true', 'false'),
        );

        $input = $factory->fromFailure(new self(__FUNCTION__), $failure);

        self::assertSame(TestIssue::TYPE_FAILED, $input->type);
        self::assertSame(self::class . '::' . __FUNCTION__, $input->testId);
        self::assertSame(self::class . '::' . __FUNCTION__, $input->testName);
        self::assertSame(__FILE__, $input->testFile);
        self::assertGreaterThan(0, $input->testLine);
        self::assertSame('Failed asserting that false is true.', $input->message);
        self::assertStringContainsString('--- Expected', (string) $input->diff);
        self::assertStringContainsString('+++ Actual', (string) $input->diff);
        self::assertStringContainsString('-true', (string) $input->diff);
        self::assertStringContainsString('+false', (string) $input->diff);
    }

    public function testFromErrorConvertsLegacyErrorToInput(): void
    {
        $factory = new LegacyTestIssueFactory();

        $input = $factory->fromError(new self(__FUNCTION__), new RuntimeException('Legacy error'));

        self::assertSame(TestIssue::TYPE_ERROR, $input->type);
        self::assertSame(self::class . '::' . __FUNCTION__, $input->testId);
        self::assertSame('Legacy error', $input->message);
        self::assertNull($input->diff);
    }

    public function testFromRiskyConvertsLegacyRiskyToInput(): void
    {
        $factory = new LegacyTestIssueFactory();

        $input = $factory->fromRisky(new self(__FUNCTION__), new RuntimeException('No assertions'));

        self::assertSame(TestIssue::TYPE_RISKY, $input->type);
        self::assertSame('No assertions', $input->message);
        self::assertSame('', $input->stackTrace);
    }
}
