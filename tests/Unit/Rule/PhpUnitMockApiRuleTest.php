<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PhpStanAiRules\Rule\PhpUnitMockApiRule;
use PhpStanAiRules\Support\TestClassScope;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @extends RuleTestCase<PhpUnitMockApiRule>
 */
#[CoversClass(PhpUnitMockApiRule::class)]
final class PhpUnitMockApiRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new PhpUnitMockApiRule(
            self::createReflectionProvider(),
            new TestClassScope(),
        );
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Expr::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeProhibitedMockApiIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/PhpUnitMockApi/ProhibitedApi.php'], [
            [
                'PHPUnit getMockBuilder() is prohibited. Use createMock(FooInterface::class) or createStub(FooInterface::class) instead. These APIs enforce interface-based test doubles for better decoupling.',
                18,
            ],
            [
                'PHPUnit createPartialMock() is prohibited. Use createMock(FooInterface::class) or createStub(FooInterface::class) instead. These APIs enforce interface-based test doubles for better decoupling.',
                23,
            ],
        ]);
    }

    public function testProcessNodeCreateMockWithConcreteClassIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/PhpUnitMockApi/ConcreteClassMock.php'], [
            [
                'PHPUnit createMock() must target an interface; "Tests\Fixture\PhpUnitMockApi\ConcreteService" is not an interface. Mock only interfaces to keep tests decoupled from implementations.',
                20,
            ],
        ]);
    }

    public function testProcessNodeCreateMockWithInterfaceIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/PhpUnitMockApi/InterfaceMock.php'], []);
    }

    public function testProcessNodeNonLiteralClassStringIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/PhpUnitMockApi/NonLiteral.php'], [
            [
                'PHPUnit createMock() must use a direct interface class-string literal (e.g. DependencyInterface::class). Variables and string literals are not allowed because the type must be statically verifiable.',
                14,
            ],
        ]);
    }
}
