<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestNamingConvention;

use PhpAiToolkit\PhpStan\Rule\TestNamingConvention\ProviderNameValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProviderNameValidator::class)]
final class ProviderNameValidatorTest extends TestCase
{
    public function testErrorsReturnsInvalidProviderNameError(): void
    {
        $errors = (new ProviderNameValidator())->errors('provider_data', 10);

        self::assertSame('customRules.providerNamingConvention', $errors[0]->getIdentifier());
    }
}
