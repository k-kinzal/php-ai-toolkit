<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Visibility;

use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

use function usort;

/**
 * Enforces namespace scopes declared with @visibility across analysed files.
 *
 * @implements Rule<CollectedDataNode>
 */
final class EnforceVisibilityScopeRule implements Rule
{
    /** @readonly */
    private VisibilityInspector $inspector;

    /** @readonly */
    private VisibilityRuleErrorBuilder $errorBuilder;

    /**
     * @param list<string> $exemptNamespacePrefixes namespace subtrees allowed to cross scopes
     */
    public function __construct(
        array $exemptNamespacePrefixes = ['Tests'],
        ?VisibilityInspector $inspector = null,
        ?VisibilityRuleErrorBuilder $errorBuilder = null,
    ) {
        $this->inspector = $inspector ?? new VisibilityInspector($exemptNamespacePrefixes);
        $this->errorBuilder = $errorBuilder ?? new VisibilityRuleErrorBuilder();
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
        $index = new VisibilityDeclarationIndex();
        foreach ($node->get(VisibilityDeclarationCollector::class) as $file => $collectedDeclarations) {
            foreach ($collectedDeclarations as $collected) {
                $index->add($file, $collected);
            }
        }

        $references = [];
        foreach ($node->get(VisibilityReferenceCollector::class) as $file => $collectedReferenceLists) {
            foreach ($collectedReferenceLists as $collectedReferences) {
                foreach ($collectedReferences as $reference) {
                    $references[] = $reference + ['file' => $file];
                }
            }
        }

        $violations = $this->inspector->violations($index, $references);
        usort($violations, static fn (array $left, array $right): int => [
            $left['file'],
            $left['line'],
            $left['identifier'],
            $left['symbol'],
        ] <=> [
            $right['file'],
            $right['line'],
            $right['identifier'],
            $right['symbol'],
        ]);

        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = $this->errorBuilder->build($violation);
        }

        return $errors;
    }
}
