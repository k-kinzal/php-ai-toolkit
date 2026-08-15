<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Parse;

use function implode;

use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;

/**
 * Prints native PHP type declarations as canonical text.
 *
 * Class names are printed fully qualified without a leading backslash so
 * the renderer can resolve them against the symbol table.
 */
final class NativeTypePrinter
{
    /**
     * Prints one native type node, or null when no type is declared.
     *
     * @param Identifier|Name|ComplexType|null $type
     */
    public function print(?Node $type): ?string
    {
        if ($type === null) {
            return null;
        }

        if ($type instanceof NullableType) {
            return '?' . $this->print($type->type);
        }

        if ($type instanceof UnionType) {
            return implode('|', $this->parts($type->types));
        }

        if ($type instanceof IntersectionType) {
            return implode('&', $this->parts($type->types));
        }

        if ($type instanceof Identifier) {
            return $type->toString();
        }

        if ($type instanceof Name) {
            return $type->toString();
        }

        return null;
    }

    /**
     * Prints the member types of a composite type node.
     *
     * @param array<Identifier|Name|ComplexType> $types
     *
     * @return list<string>
     */
    public function parts(array $types): array
    {
        $printed = [];
        foreach ($types as $type) {
            $text = $this->print($type);
            if ($text !== null) {
                $printed[] = $text;
            }
        }

        return $printed;
    }
}
