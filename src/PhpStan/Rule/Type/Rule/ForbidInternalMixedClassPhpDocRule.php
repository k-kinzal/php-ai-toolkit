<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\Type\Rule;

use PhpAiToolkit\PhpStan\Rule\Type\MixedClassPhpDocErrorCollector;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * Applies the internal mixed policy to class-level virtual declarations.
 *
 * @implements Rule<InClassNode>
 */
final class ForbidInternalMixedClassPhpDocRule implements Rule
{
    /** @readonly */
    private MixedClassPhpDocErrorCollector $collector;

    /**
     * Creates the rule from class PHPDoc inspection.
     */
    public function __construct(?MixedClassPhpDocErrorCollector $collector = null)
    {
        $this->collector = $collector ?? new MixedClassPhpDocErrorCollector();
    }

    /**
     * @return class-string<InClassNode>
     */
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        unset($scope);

        return $this->collector->errors($node);
    }
}
