<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Type;

use function array_map;
use function in_array;
use function ltrim;

use PHPStan\Reflection\ClassReflection;

use function preg_match;
use function preg_match_all;
use function str_contains;
use function str_starts_with;
use function strtolower;
use function substr_count;

/**
 * Resolves the public-versus-restricted distinction used by the mixed rule.
 *
 * @visibility namespace
 */
final class MixedVisibilityDetector
{
    private const TAG = '@visibility';

    /**
     * Reports whether a class has an effective restricted @visibility scope.
     */
    public function classIsRestricted(ClassReflection $class): bool
    {
        if ($class->isAnonymous()) {
            return true;
        }

        $reflection = $class->getNativeReflection();
        $docComment = $reflection->getDocComment();

        return $this->isRestricted($docComment === false ? null : $docComment, $reflection->getNamespaceName());
    }

    /**
     * Reports whether one PHPDoc block narrows a declaration's visibility.
     */
    public function isRestricted(?string $docComment, string $namespace): bool
    {
        $values = $this->values($docComment);
        $keywords = array_map(static fn (string $value): string => strtolower($value), $values);
        if ($values === [] || in_array('public', $keywords, true)) {
            return false;
        }

        foreach ($values as $value) {
            if ($this->valueNarrows($value, $namespace)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the declared visibility values in source order.
     *
     * @return list<string>
     */
    public function values(?string $docComment): array
    {
        if ($docComment === null || !str_contains($docComment, self::TAG)) {
            return [];
        }

        $matches = [];
        preg_match_all('/^[ \t]*(?:\/\*\*[ \t]*)?\*?[ \t]*' . self::TAG . '[ \t]+([^\s*]+)/m', $docComment, $matches);

        return $matches[1];
    }

    /**
     * Reports whether one valid tag value opens less than the whole program.
     */
    public function valueNarrows(string $value, string $namespace): bool
    {
        $keyword = str_starts_with($value, '\\') ? null : strtolower($value);
        if ($keyword === 'namespace') {
            return $namespace !== '';
        }
        if ($keyword === 'parent') {
            return substr_count($namespace, '\\') >= 1;
        }
        if ($keyword === 'root') {
            return $namespace !== '';
        }
        if ($keyword === 'public' || ($keyword !== null && !str_contains($value, '\\') && $keyword === $value)) {
            return false;
        }

        $namedNamespace = ltrim($value, '\\');

        return preg_match('/^[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*(?:\\\\[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)*$/', $namedNamespace) === 1;
    }
}
