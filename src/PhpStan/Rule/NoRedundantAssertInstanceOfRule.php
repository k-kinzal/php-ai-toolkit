<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<\PhpParser\Node\Expr>
 */
final class NoRedundantAssertInstanceOfRule implements Rule
{
    private readonly AssertInstanceOfRedundancyInspector $inspector;

    /**
     * @param TestClassScope $testClassScope test class scope detector
     */
    public function __construct(
        private readonly TestClassScope $testClassScope,
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
