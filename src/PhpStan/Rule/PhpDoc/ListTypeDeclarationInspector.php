<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\PhpDoc;

use function count;

use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

use function sprintf;
use function strtolower;

/**
 * Finds array<int, V> declarations that can be narrowed to list<V>.
 */
final class ListTypeDeclarationInspector
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
     * Returns replacements from callable return declarations.
     *
     * @return list<array{tag: string, type: string, replacement: string}>
     */
    public function returnDeclarations(string $docComment): array
    {
        $phpDoc = $this->parser->parse($docComment);
        $declarations = [];
        foreach (['@return', '@phpstan-return', '@psalm-return'] as $tagName) {
            foreach ($phpDoc->getTagsByName($tagName) as $tag) {
                if (!$tag->value instanceof ReturnTagValueNode) {
                    continue;
                }
                foreach ($this->replacements($tag->value->type) as $replacement) {
                    $declarations[] = [
                        'tag' => $tagName,
                        'type' => $replacement['type'],
                        'replacement' => $replacement['replacement'],
                    ];
                }
            }
        }

        return $declarations;
    }

    /**
     * Returns replacements from property declarations.
     *
     * @return list<array{tag: string, type: string, replacement: string, variable: string}>
     */
    public function propertyDeclarations(string $docComment): array
    {
        $phpDoc = $this->parser->parse($docComment);
        $declarations = [];
        foreach (['@var', '@phpstan-var', '@psalm-var'] as $tagName) {
            foreach ($phpDoc->getTagsByName($tagName) as $tag) {
                if (!$tag->value instanceof VarTagValueNode) {
                    continue;
                }
                foreach ($this->replacements($tag->value->type) as $replacement) {
                    $declarations[] = [
                        'tag' => $tagName,
                        'type' => $replacement['type'],
                        'replacement' => $replacement['replacement'],
                        'variable' => $tag->value->variableName,
                    ];
                }
            }
        }

        return $declarations;
    }

    /**
     * Finds direct array<int, V> branches without inspecting nested values.
     *
     * @return list<array{type: string, replacement: string}>
     */
    public function replacements(TypeNode $type): array
    {
        if ($type instanceof GenericTypeNode && $this->isArrayIntType($type)) {
            return [[
                'type' => (string) $type,
                'replacement' => sprintf('list<%s>', $this->valueType($type)),
            ]];
        }

        if ($type instanceof NullableTypeNode) {
            return $this->replacements($type->type);
        }

        if (!$type instanceof UnionTypeNode && !$type instanceof IntersectionTypeNode) {
            return [];
        }

        $replacements = [];
        foreach ($type->types as $member) {
            foreach ($this->replacements($member) as $replacement) {
                $replacements[] = $replacement;
            }
        }

        return $replacements;
    }

    /**
     * Reports whether one type is exactly array<int, V>.
     */
    public function isArrayIntType(TypeNode $type): bool
    {
        if (!$type instanceof GenericTypeNode || count($type->genericTypes) !== 2) {
            return false;
        }

        $keyType = $type->genericTypes[0];

        return strtolower($type->type->name) === 'array'
            && $keyType instanceof IdentifierTypeNode
            && strtolower($keyType->name) === 'int';
    }

    /**
     * Renders the value argument while preserving generic variance.
     */
    public function valueType(GenericTypeNode $type): string
    {
        $valueType = (string) $type->genericTypes[1];
        $variance = $type->variances[1] ?? GenericTypeNode::VARIANCE_INVARIANT;
        if ($variance === GenericTypeNode::VARIANCE_INVARIANT) {
            return $valueType;
        }
        if ($variance === GenericTypeNode::VARIANCE_BIVARIANT) {
            return '*';
        }

        return sprintf('%s %s', $variance, $valueType);
    }
}
