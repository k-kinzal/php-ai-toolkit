<?php

declare(strict_types=1);

namespace PhpStanAiRules\Rule;

use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\While_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PhpStanAiRules\Support\TestClassScope;

/**
 * @implements Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
final class NoControlFlowInTestMethodRule implements Rule
{
    /**
     * @param TestClassScope $testClassScope test class scope detector
     */
    public function __construct(
        private readonly TestClassScope $testClassScope,
    ) {
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

        if (!$this->isTestMethod($node)) {
            return [];
        }

        if ($node->stmts === null) {
            return [];
        }

        $methodName = $node->name->toString();

        return $this->findControlFlowViolations($node->stmts, $methodName);
    }

    private function isTestMethod(\PhpParser\Node\Stmt\ClassMethod $node): bool
    {
        if (str_starts_with($node->name->toString(), 'test')) {
            return true;
        }

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $attrName = $attr->name->toString();
                if ($attrName === 'Test' || str_ends_with($attrName, '\\Test')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<\PhpParser\Node\Stmt> $stmts
     * @return list<IdentifierRuleError>
     */
    private function findControlFlowViolations(array $stmts, string $methodName): array
    {
        $nodeFinder = new NodeFinder();
        $violations = $nodeFinder->find($stmts, static function (\PhpParser\Node $node): bool {
            return self::getControlFlowType($node) !== null;
        });

        $filtered = $this->excludeNestedScopes($violations, $stmts);

        $errors = [];
        foreach ($filtered as $node) {
            $statementType = self::getControlFlowType($node);
            if ($statementType === null) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Control flow statement "%s" is prohibited in test method %s(). Complex control flow in tests indicates the test is doing too much. Split into separate test methods or use data providers for parameterized cases. try-catch is allowed when testing exception behavior.',
                    $statementType,
                    $methodName
                )
            )
                ->identifier('customRules.testMethodControlFlow')
                ->line($node->getStartLine())
                ->build();
        }

        return $errors;
    }

    /**
     * @param array<\PhpParser\Node> $violations
     * @param array<\PhpParser\Node\Stmt> $stmts
     * @return list<\PhpParser\Node>
     */
    private function excludeNestedScopes(array $violations, array $stmts): array
    {
        $nodeFinder = new NodeFinder();

        /** @var list<Closure|ArrowFunction|Class_> $nestedScopes */
        $nestedScopes = $nodeFinder->find($stmts, static function (\PhpParser\Node $node): bool {
            return $node instanceof Closure || $node instanceof ArrowFunction || $node instanceof Class_;
        });

        $result = [];
        foreach ($violations as $violation) {
            if (!$this->isInsideAnyScope($violation, $nestedScopes)) {
                $result[] = $violation;
            }
        }

        return $result;
    }

    /**
     * @param list<Closure|ArrowFunction|Class_> $scopes
     */
    private function isInsideAnyScope(\PhpParser\Node $node, array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if ($node->getStartLine() >= $scope->getStartLine() && $node->getEndLine() <= $scope->getEndLine()) {
                return true;
            }
        }

        return false;
    }

    private static function getControlFlowType(\PhpParser\Node $node): ?string
    {
        if ($node instanceof If_) {
            return 'if';
        }
        if ($node instanceof For_) {
            return 'for';
        }
        if ($node instanceof Foreach_) {
            return 'foreach';
        }
        if ($node instanceof While_) {
            return 'while';
        }
        if ($node instanceof Do_) {
            return 'do-while';
        }
        if ($node instanceof Switch_) {
            return 'switch';
        }
        if ($node instanceof Match_) {
            return 'match';
        }

        return null;
    }
}
