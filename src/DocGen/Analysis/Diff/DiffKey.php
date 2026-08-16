<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Diff;

use function ltrim;
use function strtolower;

/**
 * Builds the lookup keys under which diff states are recorded.
 *
 * Symbol names are compared case-insensitively, exactly as PHP resolves
 * them, so a class that only changed the case of its name is not reported
 * as one class added next to one removed.
 */
final class DiffKey
{
    /**
     * Member kind of a class constant.
     */
    public const CONSTANT = 'constant';

    /**
     * Member kind of a property.
     */
    public const PROPERTY = 'property';

    /**
     * Member kind of a method.
     */
    public const METHOD = 'method';

    /**
     * Member kind of an enum case.
     */
    public const ENUM_CASE = 'case';

    /**
     * Returns the key of one class, interface, trait, or enum.
     */
    public function classLike(string $fqcn): string
    {
        return 'c:' . strtolower(ltrim($fqcn, '\\'));
    }

    /**
     * Returns the key of the declaration head of one class-like symbol.
     */
    public function header(string $fqcn): string
    {
        return 'h:' . strtolower(ltrim($fqcn, '\\'));
    }

    /**
     * Returns the key of one top-level function.
     */
    public function functionSymbol(string $fqn): string
    {
        return 'f:' . strtolower(ltrim($fqn, '\\'));
    }

    /**
     * Returns the key of one member of a class-like symbol.
     *
     * @param string $kind the member kind, such as method or property
     */
    public function member(string $fqcn, string $kind, string $name): string
    {
        return 'm:' . strtolower(ltrim($fqcn, '\\')) . '::' . $kind . '.' . $name;
    }

    /**
     * Returns the key of one parameter of a method or function.
     *
     * @param string $ownerKey the member or function key of the declaration
     */
    public function parameter(string $ownerKey, string $name): string
    {
        return 'p:' . $ownerKey . '#' . $name;
    }

    /**
     * Returns the key of the return type of a method or function.
     *
     * @param string $ownerKey the member or function key of the declaration
     */
    public function returnType(string $ownerKey): string
    {
        return 'r:' . $ownerKey;
    }

    /**
     * Returns the key of the throws tags of a method or function.
     *
     * @param string $ownerKey the member or function key of the declaration
     */
    public function throwsTags(string $ownerKey): string
    {
        return 't:' . $ownerKey;
    }

    /**
     * Returns the key of one rendered Markdown document.
     */
    public function document(string $packageName, string $path): string
    {
        return 'd:' . $packageName . '/' . $path;
    }

    /**
     * Returns the key of one documented package.
     */
    public function package(string $packageName): string
    {
        return 'k:' . $packageName;
    }

    /**
     * Returns the key of one namespace of a package.
     */
    public function namespaceName(string $packageName, string $namespace): string
    {
        return 'n:' . $packageName . '/' . strtolower($namespace);
    }
}
