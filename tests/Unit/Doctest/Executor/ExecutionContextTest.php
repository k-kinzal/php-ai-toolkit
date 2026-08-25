<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Executor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Toolkit\Doctest\Executor\ExecutionContext;

/**
 * @covers \Toolkit\Doctest\Executor\ExecutionContext
 */
#[CoversClass(ExecutionContext::class)]
final class ExecutionContextTest extends TestCase
{
    public function testGetVariablesStartsEmpty(): void
    {
        $context = new ExecutionContext();

        self::assertSame([], $context->getVariables());
        self::assertSame('', $context->lastOutput);
    }

    public function testSetVariablesReplacesTheKnownVariablesAndDropsInternalOnes(): void
    {
        $context = new ExecutionContext();
        $context->setVariables(['sum' => 3, '__doctest_result__' => 'leaked', 'needsReturn' => true]);

        self::assertSame(['sum' => 3], $context->getVariables());
    }

    public function testSetVariableStoresOneValue(): void
    {
        $context = new ExecutionContext();
        $context->setVariable('x', 42);

        self::assertSame(['x' => 42], $context->getVariables());
    }

    public function testGetVariableReturnsNullForAnUnknownName(): void
    {
        $context = new ExecutionContext();
        $context->setVariable('y', 99);

        self::assertSame(99, $context->getVariable('y'));
        self::assertNull($context->getVariable('missing'));
    }

    public function testHasVariableReportsWhetherTheNameIsKnown(): void
    {
        $context = new ExecutionContext();
        $context->setVariable('x', null);

        self::assertTrue($context->hasVariable('x'));
        self::assertFalse($context->hasVariable('missing'));
    }

    /**
     * @dataProvider providerInternalVariableName
     */
    #[DataProvider('providerInternalVariableName')]
    public function testSetVariablesDropsEveryInternalNameItKnows(string $name): void
    {
        $context = new ExecutionContext();
        $context->setVariables(['sum' => 3, $name => 'leaked']);

        self::assertSame(['sum' => 3], $context->getVariables());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function providerInternalVariableName(): array
    {
        return [
            'result' => ['__doctest_result__'],
            'code' => ['__doctest_code__'],
            'context' => ['__doctest_context__'],
            'variables' => ['__doctest_vars__'],
            'output' => ['__doctest_output__'],
            'variable bag' => ['variables'],
            'return flag' => ['needsReturn'],
            'evaluated code' => ['evalCode'],
        ];
    }
}
