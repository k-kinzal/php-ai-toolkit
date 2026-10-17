<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Visibility;

use function array_merge;
use function in_array;

use PhpParser\Node;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;

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
     * @return list<Name>
     */
    public function namesIn(?Node $typeNode): array
    {
        if ($typeNode instanceof Name) {
            return $this->isRelative($typeNode) ? [] : [$typeNode];
        }

        if ($typeNode instanceof NullableType) {
            return $this->namesIn($typeNode->type);
        }

        if (!$typeNode instanceof UnionType && !$typeNode instanceof IntersectionType) {
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
    public function isRelative(Name $name): bool
    {
        return in_array(strtolower($name->toString()), self::RELATIVE_KEYWORDS, true);
    }
}
