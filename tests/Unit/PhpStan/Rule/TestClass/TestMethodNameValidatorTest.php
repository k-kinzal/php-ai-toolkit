<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass;

use PhpAiToolkit\PhpStan\Rule\TestClass\TestMethodNameValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\TestClass\TestMethodNameValidator
 */
#[CoversClass(TestMethodNameValidator::class)]
final class TestMethodNameValidatorTest extends TestCase
{
    public function testErrorsReturnsInvalidTestMethodNameError(): void
    {
        $errors = (new TestMethodNameValidator())->errors('test_something', 10);

        self::assertSame('customRules.testMethodNamingConvention', $errors[0]->getIdentifier());
    }
}
