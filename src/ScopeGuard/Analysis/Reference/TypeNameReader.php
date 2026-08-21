<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Analysis\Reference;

use function array_merge;
use function in_array;
use function strtolower;

/**
 * Reads the class names written inside a type declaration.
 *
 * @visibility parent
 */
final class TypeNameReader
{
    /** @var list<string> */
    private const RELATIVE_KEYWORDS = ['self', 'static', 'parent'];

    /**
     * Returns every class name in a type, unwrapping nullable, union, and intersection types.
     *
     * @return list<\PhpParser\Node\Name>
     */
    public function namesIn(?\PhpParser\Node $typeNode): array
    {
        if ($typeNode instanceof \PhpParser\Node\Name) {
            return $this->isRelative($typeNode) ? [] : [$typeNode];
        }

        if ($typeNode instanceof \PhpParser\Node\NullableType) {
            return $this->namesIn($typeNode->type);
        }

        if (!$typeNode instanceof \PhpParser\Node\UnionType && !$typeNode instanceof \PhpParser\Node\IntersectionType) {
            return [];
        }

        $names = [];
        foreach ($typeNode->types as $memberType) {
            $names = array_merge($names, $this->namesIn($memberType));
        }

        return $names;
    }

    /**
     * Reports whether a name is self, static, or parent.
     *
     * Those three can only name the class the reference is already inside or one it
     * inherits from, and that inheritance is checked where it is declared.
     */
    public function isRelative(\PhpParser\Node\Name $name): bool
    {
        return in_array(strtolower($name->toString()), self::RELATIVE_KEYWORDS, true);
    }
}
