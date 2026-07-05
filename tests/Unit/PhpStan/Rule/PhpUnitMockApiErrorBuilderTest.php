<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\PhpUnitMockApiErrorBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpUnitMockApiErrorBuilder::class)]
final class PhpUnitMockApiErrorBuilderTest extends TestCase
{
    public function testProhibitedApiReturnsProhibitedApiError(): void
    {
        self::assertSame(
            'customRules.testClassPhpUnitMockProhibitedApi',
            (new PhpUnitMockApiErrorBuilder())->prohibitedApi('getMockBuilder', 1)->getIdentifier(),
        );
    }

    public function testRequiresLiteralInterfaceReturnsLiteralInterfaceError(): void
    {
        self::assertSame(
            'customRules.testClassPhpUnitMockRequiresLiteralInterface',
            (new PhpUnitMockApiErrorBuilder())->requiresLiteralInterface('createMock', 1)->getIdentifier(),
        );
    }

    public function testRequiresInterfaceReturnsInterfaceError(): void
    {
        self::assertSame(
            'customRules.testClassPhpUnitMockRequiresInterface',
            (new PhpUnitMockApiErrorBuilder())->requiresInterface('createMock', 'App\\Service', 1)->getIdentifier(),
        );
    }

    public function testProhibitedInstantiationReturnsInstantiationError(): void
    {
        self::assertSame(
            'customRules.testClassPhpUnitMockProhibitedInstantiation',
            (new PhpUnitMockApiErrorBuilder())->prohibitedInstantiation('PHPUnit\\Framework\\MockObject\\MockBuilder', 1)->getIdentifier(),
        );
    }
}
