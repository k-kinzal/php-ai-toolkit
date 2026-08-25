<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\Type\Rule;

use PhpAiToolkit\PhpStan\Rule\Type\MixedCallableErrorCollector;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClosureNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * Applies the internal mixed policy to closures.
 *
 * @implements Rule<InClosureNode>
 */
final class ForbidInternalMixedClosureRule implements Rule
{
    /** @readonly */
    private MixedCallableErrorCollector $collector;

    /**
     * Creates the rule from callable declaration inspection.
     */
    public function __construct(?MixedCallableErrorCollector $collector = null)
    {
        $this->collector = $collector ?? new MixedCallableErrorCollector();
    }

    /**
     * @return class-string<InClosureNode>
     */
    public function getNodeType(): string
    {
        return InClosureNode::class;
    }

    /**
     * @param InClosureNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        unset($scope);

        return $this->collector->closure($node);
    }
}
