<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Reads expression arguments from PHP-Parser call argument lists.
 */
final class CallArgumentResolver
{
    /**
     * @param array<array-key, \PhpParser\Node\Arg|\PhpParser\Node\VariadicPlaceholder> $args
     */
    public function valueAt(array $args, int $position): ?\PhpParser\Node\Expr
    {
        $arg = $args[$position] ?? null;

        return $arg instanceof \PhpParser\Node\Arg ? $arg->value : null;
    }

    /**
     * @param array<array-key, \PhpParser\Node\Arg|\PhpParser\Node\VariadicPlaceholder> $args
     */
    public function firstValue(array $args): ?\PhpParser\Node\Expr
    {
        return $this->valueAt($args, 0);
    }
}
