<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpParser\NodeFinder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Collects control-flow violations from one test method body.
 */
final class TestMethodControlFlowErrorCollector
{
    /** @readonly */
    private ControlFlowTypeResolver $typeResolver;

    /** @readonly */
    private NestedScopeFilter $nestedScopeFilter;

    /**
     * Creates a collector from type resolution and nested-scope filtering.
     */
    public function __construct(
        ?ControlFlowTypeResolver $typeResolver = null,
        ?NestedScopeFilter $nestedScopeFilter = null,
    ) {
        $this->typeResolver = $typeResolver ?? new ControlFlowTypeResolver();
        $this->nestedScopeFilter = $nestedScopeFilter ?? new NestedScopeFilter();
    }

    /**
     * @param array<\PhpParser\Node\Stmt> $stmts
     * @return list<IdentifierRuleError>
     */
    public function errors(array $stmts, string $methodName): array
    {
        $nodeFinder = new NodeFinder();
        $violations = $nodeFinder->find($stmts, function (\PhpParser\Node $node): bool {
            return $this->typeResolver->type($node) !== null;
        });

        $filtered = $this->nestedScopeFilter->filter($violations, $stmts);

        $errors = [];
        foreach ($filtered as $node) {
            $statementType = $this->typeResolver->type($node);
            if ($statementType === null) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Split test method %s() so it contains no "%s" statement. Use separate tests or a data provider for each case.',
                    $methodName,
                    $statementType
                )
            )
                ->identifier('customRules.testMethodControlFlow')
                ->line($node->getStartLine())
                ->build();
        }

        return $errors;
    }
}
