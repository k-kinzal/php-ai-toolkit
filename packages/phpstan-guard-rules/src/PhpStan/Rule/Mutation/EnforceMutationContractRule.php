<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Mutation;

use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function usort;

/**
 * Enforces explicit parameter, receiver, and global mutation effects.
 *
 * @implements Rule<CollectedDataNode>
 */
final class EnforceMutationContractRule implements Rule
{
    /** @readonly */
    private MutationInspector $inspector;

    /**
     * Creates the rule from the whole-program mutation inspector.
     */
    public function __construct(?MutationInspector $inspector = null)
    {
        $this->inspector = $inspector ?? new MutationInspector();
    }

    /**
     * @return class-string<CollectedDataNode>
     */
    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /**
     * @param CollectedDataNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        unset($scope);
        $declarations = [];
        foreach ($node->get(MutationDeclarationCollector::class) as $file => $collected) {
            foreach ($collected as $declaration) {
                $declarations[] = $declaration + ['file' => $file];
            }
        }

        $operations = [];
        foreach ($node->get(MutationOperationCollector::class) as $file => $collectedLists) {
            foreach ($collectedLists as $collected) {
                foreach ($collected as $operation) {
                    $operations[] = $operation + ['file' => $file];
                }
            }
        }

        $violations = $this->inspector->violations($declarations, $operations);
        usort($violations, static fn (array $left, array $right): int => [$left['file'], $left['line'], $left['identifier'], $left['symbol']] <=> [$right['file'], $right['line'], $right['identifier'], $right['symbol']]);

        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = RuleErrorBuilder::message($violation['message'])
                ->file($violation['file'])
                ->line($violation['line'])
                ->identifier($violation['identifier'])
                ->metadata(['symbol' => $violation['symbol']])
                ->build();
        }

        return $errors;
    }
}
