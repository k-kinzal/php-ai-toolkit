<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\Type\Rule;

use PhpAiToolkit\PhpStan\Rule\Type\MixedPropertyErrorCollector;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * Applies the internal mixed policy to stored properties.
 *
 * @implements Rule<ClassPropertyNode>
 */
final class ForbidInternalMixedPropertyRule implements Rule
{
    /** @readonly */
    private MixedPropertyErrorCollector $collector;

    /**
     * Creates the rule from property declaration inspection.
     */
    public function __construct(?MixedPropertyErrorCollector $collector = null)
    {
        $this->collector = $collector ?? new MixedPropertyErrorCollector();
    }

    /**
     * @return class-string<ClassPropertyNode>
     */
    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    /**
     * @param ClassPropertyNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        unset($scope);

        return $this->collector->errors($node);
    }
}
