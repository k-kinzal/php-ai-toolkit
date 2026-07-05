<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<\PhpParser\Node\Expr>
 */
final class ForbiddenMagicMethodCallRule implements Rule
{
    private readonly MagicMethodCallInspector $callInspector;

    /**
     * Creates the rule from call inspection.
     */
    public function __construct(?MagicMethodCallInspector $callInspector = null)
    {
        $this->callInspector = $callInspector ?? new MagicMethodCallInspector();
    }

    /**
     * @return class-string<\PhpParser\Node\Expr>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Expr::class;
    }

    /**
     * @param \PhpParser\Node\Expr $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        unset($scope);

        return $this->callInspector->errors($node);
    }
}
