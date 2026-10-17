<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ClassDesign;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Rules\IdentifierRuleError;

/**
 * Inspects method and static calls for direct magic method calls.
 */
final class MagicMethodCallInspector
{
    /** @readonly */
    private MagicMethodRegistry $magicMethodRegistry;

    /** @readonly */
    private MagicMethodCallErrorBuilder $errorBuilder;

    /**
     * Creates an inspector from magic method registry and error building.
     */
    public function __construct(
        ?MagicMethodRegistry $magicMethodRegistry = null,
        ?MagicMethodCallErrorBuilder $errorBuilder = null,
    ) {
        $this->magicMethodRegistry = $magicMethodRegistry ?? new MagicMethodRegistry();
        $this->errorBuilder = $errorBuilder ?? new MagicMethodCallErrorBuilder();
    }

    /**
     * Returns direct magic method call errors for the expression.
     *
     * @return list<IdentifierRuleError>
     */
    public function errors(Expr $node): array
    {
        if ($node instanceof MethodCall) {
            if (!$node->name instanceof Identifier) {
                return [];
            }

            $methodName = $node->name->toString();
            return $this->magicMethodRegistry->isMagic($methodName) ? [$this->errorBuilder->error($methodName, $node->getStartLine())] : [];
        }

        if (!$node instanceof StaticCall) {
            return [];
        }

        if (!$node->name instanceof Identifier) {
            return [];
        }

        $methodName = $node->name->toString();
        if (!$this->magicMethodRegistry->isMagic($methodName)) {
            return [];
        }

        if ($node->class instanceof Name && strtolower($node->class->toString()) === 'parent') {
            return [];
        }

        return [$this->errorBuilder->error($methodName, $node->getStartLine())];
    }
}
