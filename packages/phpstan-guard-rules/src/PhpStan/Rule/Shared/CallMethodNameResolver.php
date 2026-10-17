<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Shared;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;

/**
 * Resolves method names from PHP-Parser call name nodes.
 */
final class CallMethodNameResolver
{
    /**
     * Returns the called method name when it is statically known.
     */
    public function resolve(Identifier|Expr $name): ?string
    {
        if (!$name instanceof Identifier) {
            return null;
        }

        return $name->toString();
    }
}
