<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\PhpUnitMockApiRule;
use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<PhpUnitMockApiRule>
 */
#[CoversClass(PhpUnitMockApiRule::class)]
#[Medium]
final class PhpUnitMockApiRuleTest extends RuleTestCase
{
    #[Override]
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
        $this->analyse([__DIR__ . '/../../../Fixture/PhpUnitMockApi/ProhibitedApi.php'], [
            [
                'Use createMock(FooInterface::class) or createStub(FooInterface::class) instead of PHPUnit getMockBuilder().',
                18,
            ],
            [
                'Use createMock(FooInterface::class) or createStub(FooInterface::class) instead of PHPUnit createPartialMock().',
                23,
            ],
        ]);
    }

    public function testProcessNodeCreateMockWithConcreteClassIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/PhpUnitMockApi/ConcreteClassMock.php'], [
            [
                'Pass an interface class-string to PHPUnit createMock(); "Tests\Fixture\PhpUnitMockApi\ConcreteService" is not an interface.',
                20,
            ],
        ]);
    }

    public function testProcessNodeCreateMockWithInterfaceIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/PhpUnitMockApi/InterfaceMock.php'], []);
    }

    public function testProcessNodeNonLiteralClassStringIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/PhpUnitMockApi/NonLiteral.php'], [
            [
                'Pass an interface class-string literal to PHPUnit createMock(), e.g. DependencyInterface::class. Do not pass variables or plain strings.',
                14,
            ],
        ]);
    }
}
