<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<\PhpParser\Node\Expr>
 */
final class PhpUnitMockApiRule implements Rule
{
    /** @readonly */
    private PhpUnitMockApiCallInspector $callInspector;

    /** @readonly */
    private PhpUnitMockInstantiationInspector $instantiationInspector;

    /**
     * @param ReflectionProvider $reflectionProvider PHPStan reflection provider
     * @param TestClassScope $testClassScope test class scope detector
     */
    public function __construct(
        ReflectionProvider $reflectionProvider,
        /** @readonly */
        private TestClassScope $testClassScope,
        ?PhpUnitMockApiCallInspector $callInspector = null,
        ?PhpUnitMockInstantiationInspector $instantiationInspector = null,
    ) {
        $this->callInspector = $callInspector ?? new PhpUnitMockApiCallInspector($reflectionProvider);
        $this->instantiationInspector = $instantiationInspector ?? new PhpUnitMockInstantiationInspector();
    }

    /**
     * @return class-string<\PhpParser\Node\Expr>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Expr::class;
    }

    /**
     * @param \PhpParser\Node\Expr $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if (!$this->testClassScope->isTestClass($scope)) {
            return [];
        }

        if ($node instanceof \PhpParser\Node\Expr\New_) {
            return $this->instantiationInspector->errors($node, $scope);
        }

        return $this->callInspector->errors($node, $scope);
    }
}
