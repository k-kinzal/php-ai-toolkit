<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use function interface_exists;

use Override;
use PhpAiToolkit\PhpUnit\TestReporter\EventTestIssueFactory;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssue;
use PHPUnit\Event\Code\ComparisonFailure;
use PHPUnit\Event\Code\TestDox;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Code\Throwable;
use PHPUnit\Event\Test\ConsideredRisky;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\TestData\TestDataCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Metadata\MetadataCollection;
use Tests\Fixture\PhpUnitInternalObjectFactory;

#[CoversClass(EventTestIssueFactory::class)]
final class EventTestIssueFactoryTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        if (!interface_exists('PHPUnit\Runner\Extension\Extension')) {
            self::markTestSkipped('Requires PHPUnit 10 event extension API.');
        }
    }

    public function testFromFailureConvertsEventToInput(): void
    {
        $factory = new EventTestIssueFactory();
        $telemetryInfo = PhpUnitInternalObjectFactory::telemetryInfo();
        $test = new TestMethod(
            self::class,
            'testBar',
            '/path/to/tests/FooTest.php',
            42,
            new TestDox('', '', ''),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );
        $event = new Failed(
            $telemetryInfo,
            $test,
            new Throwable('Exception', 'Failed', 'Failed', '/path/to/tests/FooTest.php:42', null),
            new ComparisonFailure('true', 'false', "--- Expected\n+++ Actual\n-true\n+false"),
        );

        $input = $factory->fromFailure($event);

        self::assertSame(TestIssue::TYPE_FAILED, $input->type);
        self::assertSame(self::class . '::testBar', $input->testId);
        self::assertSame(self::class . '::testBar', $input->testName);
        self::assertSame('/path/to/tests/FooTest.php', $input->testFile);
        self::assertSame(42, $input->testLine);
        self::assertSame('Failed', $input->message);
        self::assertSame("--- Expected\n+++ Actual\n-true\n+false", $input->diff);
        self::assertSame('/path/to/tests/FooTest.php:42', $input->stackTrace);
    }

    public function testFromErrorConvertsEventToInput(): void
    {
        $factory = new EventTestIssueFactory();
        $telemetryInfo = PhpUnitInternalObjectFactory::telemetryInfo();
        $test = new TestMethod(
            self::class,
            'testBaz',
            '/path/to/tests/BarTest.php',
            18,
            new TestDox('', '', ''),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );
        $event = new Errored(
            $telemetryInfo,
            $test,
            new Throwable('TypeError', 'Broken', 'Broken', '/path/to/tests/BarTest.php:18', null),
        );

        $input = $factory->fromError($event);

        self::assertSame(TestIssue::TYPE_ERROR, $input->type);
        self::assertSame(self::class . '::testBaz', $input->testId);
        self::assertSame('Broken', $input->message);
        self::assertNull($input->diff);
    }

    public function testFromRiskyConvertsEventToInput(): void
    {
        $factory = new EventTestIssueFactory();
        $telemetryInfo = PhpUnitInternalObjectFactory::telemetryInfo();
        $test = new TestMethod(
            self::class,
            'testRisk',
            '/path/to/tests/RiskyTest.php',
            9,
            new TestDox('', '', ''),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );
        $event = new ConsideredRisky(
            $telemetryInfo,
            $test,
            'This test did not perform any assertions',
        );

        $input = $factory->fromRisky($event);

        self::assertSame(TestIssue::TYPE_RISKY, $input->type);
        self::assertSame(self::class . '::testRisk', $input->testId);
        self::assertSame('This test did not perform any assertions', $input->message);
        self::assertSame('', $input->stackTrace);
    }
}
