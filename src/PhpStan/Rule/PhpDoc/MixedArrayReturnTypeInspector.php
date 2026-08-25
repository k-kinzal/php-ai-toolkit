<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\PhpDoc;

use function count;
use function get_object_vars;
use function is_array;

use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

use function strtolower;

/**
 * Finds generic arrays with an explicit mixed value in return declarations.
 */
final class MixedArrayReturnTypeInspector
{
    /** @readonly */
    private RulePhpDocParser $parser;

    /**
     * Creates the inspector from the cross-version PHPDoc parser.
     */
    public function __construct(?RulePhpDocParser $parser = null)
    {
        $this->parser = $parser ?? new RulePhpDocParser();
    }

    /**
     * Returns every forbidden return declaration in source order by tag family.
     *
     * PHPStan and Psalm variants are checked because either can refine or
     * replace the ordinary return tag during static analysis.
     *
     * @return list<array{tag: string, type: string}>
     */
    public function declarations(string $docComment): array
    {
        $phpDoc = $this->parser->parse($docComment);
        $declarations = [];
        foreach (['@return', '@phpstan-return', '@psalm-return'] as $tagName) {
            foreach ($phpDoc->getTagsByName($tagName) as $tag) {
                if (!$tag->value instanceof ReturnTagValueNode || !$this->containsMixedArray($tag->value->type)) {
                    continue;
                }

                $declarations[] = [
                    'tag' => $tagName,
                    'type' => (string) $tag->value->type,
                ];
            }
        }

        return $declarations;
    }

    /**
     * Reports whether a type contains array<K, mixed> or array<mixed>.
     *
     * Nested occurrences are included: wrapping an uninformative array in a
     * union, generic collection, or another array does not make it precise.
     */
    public function containsMixedArray(TypeNode $type): bool
    {
        if ($this->isMixedArray($type)) {
            return true;
        }

        foreach (get_object_vars($type) as $child) {
            if ($child instanceof TypeNode && $this->containsMixedArray($child)) {
                return true;
            }
            if (!is_array($child)) {
                continue;
            }
            foreach ($child as $nested) {
                if ($nested instanceof TypeNode && $this->containsMixedArray($nested)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Reports whether the node itself is a generic array with mixed values.
     */
    public function isMixedArray(TypeNode $type): bool
    {
        if (!$type instanceof GenericTypeNode) {
            return false;
        }

        $arrayName = strtolower($type->type->name);
        if ($arrayName !== 'array' && $arrayName !== 'non-empty-array') {
            return false;
        }

        $argumentCount = count($type->genericTypes);
        if ($argumentCount !== 1 && $argumentCount !== 2) {
            return false;
        }

        $valueType = $type->genericTypes[$argumentCount - 1];

        return $valueType instanceof IdentifierTypeNode && strtolower($valueType->name) === 'mixed';
    }
}
