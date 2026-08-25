<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\Type;

use function get_object_vars;
use function is_array;
use function is_object;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

use function strtolower;

/**
 * Finds explicit mixed in PHPDoc syntax that has no reflected symbol type.
 *
 * @visibility namespace
 */
final class PhpDocMixedTypeInspector
{
    /**
     * Reports whether a PHPDoc type node contains an explicit mixed keyword.
     */
    public function contains(TypeNode $type): bool
    {
        return $this->objectContains($type);
    }

    /**
     * Recurses through one PHPDoc AST object.
     */
    public function objectContains(object $node): bool
    {
        if ($node instanceof IdentifierTypeNode && strtolower($node->name) === 'mixed') {
            return true;
        }

        foreach (get_object_vars($node) as $child) {
            if (is_object($child) && $this->objectContains($child)) {
                return true;
            }
            if (!is_array($child)) {
                continue;
            }
            foreach ($child as $nested) {
                if (is_object($nested) && $this->objectContains($nested)) {
                    return true;
                }
            }
        }

        return false;
    }
}
