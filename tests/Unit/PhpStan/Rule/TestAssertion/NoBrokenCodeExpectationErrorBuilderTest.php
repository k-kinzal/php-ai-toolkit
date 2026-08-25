<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestAssertion;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\TestAssertion\NoBrokenCodeExpectationErrorBuilder;

/**
 * @covers \Toolkit\PhpStan\Rule\TestAssertion\NoBrokenCodeExpectationErrorBuilder
 */
#[CoversClass(NoBrokenCodeExpectationErrorBuilder::class)]
final class NoBrokenCodeExpectationErrorBuilderTest extends TestCase
{
    public function testBuildReturnsBrokenCodeExpectationIdentifier(): void
    {
        $error = (new NoBrokenCodeExpectationErrorBuilder())->build('expectException', 'TypeError', 'it is broken', 12);

        self::assertSame('customRules.noBrokenCodeExpectation', $error->getIdentifier());
    }

    public function testBuildNamesTheExpectedClassAndCall(): void
    {
        $error = (new NoBrokenCodeExpectationErrorBuilder())->build('expectExceptionObject', 'LogicException', 'it is broken', 12);

        self::assertStringContainsString('expecting "LogicException" in expectExceptionObject()', $error->getMessage());
    }
}
