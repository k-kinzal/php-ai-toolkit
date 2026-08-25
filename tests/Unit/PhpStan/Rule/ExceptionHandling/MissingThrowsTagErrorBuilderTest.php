<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ExceptionHandling;

use PhpAiToolkit\PhpStan\Rule\ExceptionHandling\MissingThrowsTagErrorBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\ExceptionHandling\MissingThrowsTagErrorBuilder
 */
#[CoversClass(MissingThrowsTagErrorBuilder::class)]
final class MissingThrowsTagErrorBuilderTest extends TestCase
{
    public function testUndeclaredThrowAsksForATagOnAConcreteClass(): void
    {
        $error = (new MissingThrowsTagErrorBuilder())->undeclaredThrow('RuntimeException', 'run', 12);

        self::assertSame(
            'Declare "@throws \RuntimeException" in the PHPDoc of run() or catch the exception inside the method. The exception thrown here escapes run() without being declared.',
            $error->getMessage()
        );
    }

    public function testUndeclaredThrowCarriesTheIdentifier(): void
    {
        $error = (new MissingThrowsTagErrorBuilder())->undeclaredThrow('RuntimeException', 'run', 12);

        self::assertSame('customRules.missingThrowsTag', $error->getIdentifier());
    }

    public function testUndeclaredThrowAsksForAConcreteClassInsteadOfException(): void
    {
        $error = (new MissingThrowsTagErrorBuilder())->undeclaredThrow('Exception', 'run', 12);

        self::assertSame(
            'Throw a concrete exception class here instead of \Exception, then declare it with "@throws" in the PHPDoc of run(). Declaring "@throws \Exception" is rejected as a generic tag and catching \Exception is rejected as a broad catch, so neither of those resolves this.',
            $error->getMessage()
        );
    }

    public function testUndeclaredThrowAsksForAConcreteClassInsteadOfThrowable(): void
    {
        $error = (new MissingThrowsTagErrorBuilder())->undeclaredThrow('Throwable', 'run', 12);

        self::assertStringContainsString(
            'Throw a concrete exception class here instead of \Throwable',
            $error->getMessage()
        );
    }
}
