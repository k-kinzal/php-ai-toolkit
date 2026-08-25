<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Type\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Node\InFunctionNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use Toolkit\PhpStan\Rule\Type\MixedCallableErrorCollector;

/**
 * Applies the internal mixed policy to named functions.
 *
 * @implements Rule<InFunctionNode>
 */
final class ForbidInternalMixedFunctionRule implements Rule
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
     * @return class-string<InFunctionNode>
     */
    public function getNodeType(): string
    {
        return InFunctionNode::class;
    }

    /**
     * @param InFunctionNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        unset($scope);

        return $this->collector->function($node);
    }
}
