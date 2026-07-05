<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\TestMethodFileReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestMethodFileReader::class)]
final class TestMethodFileReaderTest extends TestCase
{
    public function testMethodNamesReturnsTestMethods(): void
    {
        self::assertSame(
            ['testProcessReturnsTrue', 'testGetNameReturnsString'],
            (new TestMethodFileReader())->methodNames(__DIR__ . '/../../../Fixture/TestNamingConvention/tests/Unit/CoveredServiceTest.php'),
        );
    }
}
