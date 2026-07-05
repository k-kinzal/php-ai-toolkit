<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use function dirname;

use PhpAiToolkit\PhpUnit\TestReporter\TestIssueSourceReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestIssueSourceReader::class)]
final class TestIssueSourceReaderTest extends TestCase
{
    public function testReadReturnsRequestedSourceLine(): void
    {
        $reader = new TestIssueSourceReader();
        $file = dirname(__DIR__, 3) . '/Fixture/TestReporter/SampleTest.php';

        self::assertSame("        self::assertSame('John', \$this->service->getName());", $reader->read($file, 11));
    }

    public function testReadReturnsNullForMissingLine(): void
    {
        $reader = new TestIssueSourceReader();

        self::assertNull($reader->read('/missing.php', 1));
    }
}
