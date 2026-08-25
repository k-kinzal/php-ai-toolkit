<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\Type;

use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * Forbids concrete mixed outside public and inherited type contracts.
 *
 * @implements Rule<InClassMethodNode>
 */
final class ForbidInternalMixedTypeRule implements Rule
{
    /** @readonly */
    private MixedCallableErrorCollector $callableCollector;

    /**
     * Creates the rule from callable declaration inspection.
     */
    public function __construct(?MixedCallableErrorCollector $callableCollector = null)
    {
        $this->callableCollector = $callableCollector ?? new MixedCallableErrorCollector();
    }

    /**
     * @return class-string<InClassMethodNode>
     */
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    /**
     * @param InClassMethodNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        unset($scope);

        return $this->callableCollector->classMethod($node);
    }
}
