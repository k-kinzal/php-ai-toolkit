<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use Toolkit\PhpStan\Rule\Shared\TestMethodDetector;
use Toolkit\PhpStan\Rule\TestClass\TestMethodControlFlowErrorCollector;
use Toolkit\PhpStan\Support\TestClassScope;

/**
 * @implements Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
final class NoControlFlowInTestMethodRule implements Rule
{
    /** @readonly */
    private TestMethodDetector $testMethodDetector;

    /** @readonly */
    private TestMethodControlFlowErrorCollector $errorCollector;

    /**
     * @param TestClassScope $testClassScope test class scope detector
     */
    public function __construct(
        /** @readonly */
        private TestClassScope $testClassScope,
        ?TestMethodDetector $testMethodDetector = null,
        ?TestMethodControlFlowErrorCollector $errorCollector = null,
    ) {
        $this->testMethodDetector = $testMethodDetector ?? new TestMethodDetector();
        $this->errorCollector = $errorCollector ?? new TestMethodControlFlowErrorCollector();
    }

    /**
     * @return class-string<ClassMethod>
     */
    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
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
