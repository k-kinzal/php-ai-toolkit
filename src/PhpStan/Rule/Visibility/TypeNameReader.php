<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Visibility;

use function array_merge;
use function in_array;
use function strtolower;

/**
 * Reads written class names from native type declarations.
 */
final class TypeNameReader
{
    /** @var list<string> */
    private const RELATIVE_KEYWORDS = ['self', 'static', 'parent'];

    /**
     * Returns every class name in a nullable, union, or intersection type.
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
     * Reports whether the name is self, static, or parent.
     */
    public function isRelative(\PhpParser\Node\Name $name): bool
    {
        return in_array(strtolower($name->toString()), self::RELATIVE_KEYWORDS, true);
    }
}
