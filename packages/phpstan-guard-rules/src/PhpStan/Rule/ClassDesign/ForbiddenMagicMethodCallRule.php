<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ClassDesign;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<\PhpParser\Node\Expr>
 */
final class ForbiddenMagicMethodCallRule implements Rule
{
    /** @readonly */
    private MagicMethodCallInspector $callInspector;

    /**
     * Creates the rule from call inspection.
     */
    public function __construct(?MagicMethodCallInspector $callInspector = null)
    {
        $this->callInspector = $callInspector ?? new MagicMethodCallInspector();
    }

    /**
     * @return class-string<Expr>
     */
    public function getNodeType(): string
    {
        return Expr::class;
    }

    /**
     * @param Expr $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        unset($scope);

        return $this->callInspector->errors($node);
    }
}
