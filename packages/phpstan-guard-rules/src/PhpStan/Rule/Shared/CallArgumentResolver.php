<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Shared;

use PhpParser\Node\Arg;
use PhpParser\Node\ArgPlaceholder;
use PhpParser\Node\Expr;
use PhpParser\Node\VariadicPlaceholder;

/**
 * Reads expression arguments from PHP-Parser call argument lists.
 */
final class CallArgumentResolver
{
    /**
     * @param array<array-key, Arg|ArgPlaceholder|VariadicPlaceholder> $args
     */
    public function valueAt(array $args, int $position): ?Expr
    {
        $arg = $args[$position] ?? null;

        return $arg instanceof Arg ? $arg->value : null;
    }

    /**
     * @param array<array-key, Arg|ArgPlaceholder|VariadicPlaceholder> $args
     */
    public function firstValue(array $args): ?Expr
    {
        return $this->valueAt($args, 0);
    }
}
