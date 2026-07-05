<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
final class NoControlFlowInTestMethodRule implements Rule
{
    private readonly TestMethodDetector $testMethodDetector;

    private readonly TestMethodControlFlowErrorCollector $errorCollector;

    /**
     * @param TestClassScope $testClassScope test class scope detector
     */
    public function __construct(
        private readonly TestClassScope $testClassScope,
        ?TestMethodDetector $testMethodDetector = null,
        ?TestMethodControlFlowErrorCollector $errorCollector = null,
    ) {
        $this->testMethodDetector = $testMethodDetector ?? new TestMethodDetector();
        $this->errorCollector = $errorCollector ?? new TestMethodControlFlowErrorCollector();
    }

    /**
     * @return class-string<\PhpParser\Node\Stmt\ClassMethod>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\ClassMethod::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassMethod $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if (!$this->testClassScope->isRestrictedTestClass($scope)) {
            return [];
        }

        if (!$this->testMethodDetector->isTestMethod($node)) {
            return [];
        }

        if ($node->stmts === null) {
            return [];
        }

        $methodName = $node->name->toString();

        return $this->errorCollector->errors($node->stmts, $methodName);
    }
}
