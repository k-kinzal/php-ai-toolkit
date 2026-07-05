<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Resolves method names from PHP-Parser call name nodes.
 */
final class CallMethodNameResolver
{
    /**
     * Returns the called method name when it is statically known.
     */
    public function resolve(\PhpParser\Node\Identifier|\PhpParser\Node\Expr $name): ?string
    {
        if (!$name instanceof \PhpParser\Node\Identifier) {
            return null;
        }

        return $name->toString();
    }
}
