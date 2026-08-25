<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestAssertion;

use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\TestAssertion\PhpUnitMockApiErrorBuilder;
use Toolkit\PhpStan\Rule\TestAssertion\PhpUnitMockInstantiationInspector;

/**
 * @covers \Toolkit\PhpStan\Rule\TestAssertion\PhpUnitMockInstantiationInspector
 * @uses \Toolkit\PhpStan\Rule\TestAssertion\PhpUnitMockApiErrorBuilder
 */
#[CoversClass(PhpUnitMockInstantiationInspector::class)]
#[UsesClass(PhpUnitMockApiErrorBuilder::class)]
final class PhpUnitMockInstantiationInspectorTest extends TestCase
{
    public function testErrorsReturnsInstantiationError(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('PHPUnit\\Framework\\MockObject\\MockBuilder');
        $newExpression = new \PhpParser\Node\Expr\New_(new \PhpParser\Node\Name('MockBuilder'));

        self::assertCount(1, (new PhpUnitMockInstantiationInspector())->errors($newExpression, $scope));
    }
}
