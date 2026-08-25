<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Reference;

use PhpAiToolkit\DocGen\Analysis\Reference\TestCase as ReferenceTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Reference\TestCase
 */
#[CoversClass(ReferenceTestCase::class)]
final class TestCaseTest extends TestCase
{
    public function testStoresTestIdentityAndOrigin(): void
    {
        $testCase = new ReferenceTestCase('Tests\Unit\GreeterTest', 'testGreet', 'tests/Unit/GreeterTest.php', 21, ReferenceTestCase::ORIGIN_CALL);

        self::assertSame('Tests\Unit\GreeterTest', $testCase->testClass);
        self::assertSame('testGreet', $testCase->testMethod);
        self::assertSame('tests/Unit/GreeterTest.php', $testCase->file);
        self::assertSame(21, $testCase->line);
        self::assertSame('call', $testCase->origin);
    }

    public function testStoresUnknownMethodFileAndLineAsNull(): void
    {
        $testCase = new ReferenceTestCase('Tests\Unit\GreeterTest', null, null, null, ReferenceTestCase::ORIGIN_COVERAGE);

        self::assertNull($testCase->testMethod);
        self::assertNull($testCase->file);
        self::assertNull($testCase->line);
        self::assertSame('coverage', $testCase->origin);
    }

    public function testStoresBothOriginsUnderOneName(): void
    {
        $testCase = new ReferenceTestCase('Tests\Unit\GreeterTest', 'testGreet', 'tests/Unit/GreeterTest.php', 21, ReferenceTestCase::ORIGIN_BOTH);

        self::assertSame('coverage+call', $testCase->origin);
    }
}
