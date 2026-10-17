<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\TestAssertion;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use Toolkit\PhpStan\Support\TestClassScope;

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
     * @return class-string<Expr>
     */
    public function getNodeType(): string
    {
        return Expr::class;
    }

    /**
     * @param Expr $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->testClassScope->isTestClass($scope)) {
            return [];
        }

        if ($node instanceof New_) {
            return $this->instantiationInspector->errors($node, $scope);
        }

        return $this->callInspector->errors($node, $scope);
    }
}
