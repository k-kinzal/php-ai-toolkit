<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\TestAssertion;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use Toolkit\PhpStan\Support\TestClassScope;

/**
 * @implements Rule<\PhpParser\Node\Expr>
 */
final class NoRedundantAssertInstanceOfRule implements Rule
{
    /** @readonly */
    private AssertInstanceOfRedundancyInspector $inspector;

    /**
     * @param TestClassScope $testClassScope test class scope detector
     */
    public function __construct(
        /** @readonly */
        private TestClassScope $testClassScope,
        ?AssertInstanceOfRedundancyInspector $inspector = null,
    ) {
        $this->inspector = $inspector ?? new AssertInstanceOfRedundancyInspector();
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

        return $this->inspector->errors($node, $scope);
    }
}
