<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Analysis\Declaration;

use function sprintf;
use function strtolower;

/**
 * Every declaration found in the analyzed sources, addressable by name.
 *
 * Member lookups walk the declared parents, so a scope written on an inherited
 * member still answers for the child class that a reference names.
 *
 * @visibility parent
 */
final class DeclarationIndex
{
    /** @var array<string, Declaration> */
    private array $classes = [];

    /** @var array<string, Declaration> */
    private array $members = [];

    /** @var array<string, list<string>> */
    private array $parents = [];

    /** @var list<Declaration> */
    private array $declarations = [];

    /**
     * Records one class-like declaration together with the types it builds on.
     *
     * @param list<string> $parents fully qualified parents, interfaces, and traits
     */
    public function addClass(string $className, array $parents, Declaration $declaration): void
    {
        $key = strtolower($className);
        $this->classes[$key] = $declaration;
        $this->parents[$key] = $parents;
        $this->declarations[] = $declaration;
    }

    /**
     * Records one member declaration of a class-like.
     */
    public function addMember(string $className, string $memberName, Declaration $declaration): void
    {
        $this->members[$this->memberKey($className, $memberName)] = $declaration;
        $this->declarations[] = $declaration;
    }

    /**
     * Returns the declaration of a class-like, or null when it is outside the analyzed sources.
     */
    public function classDeclaration(string $className): ?Declaration
    {
        return $this->classes[strtolower($className)] ?? null;
    }

    /**
     * Returns the declaration of a member, following the declared parents.
     */
    public function memberDeclaration(string $className, string $memberName): ?Declaration
    {
        $seen = [];
        $queue = [$className];

        while ($queue !== []) {
            $current = array_shift($queue);
            $key = strtolower($current);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $declaration = $this->members[$this->memberKey($current, $memberName)] ?? null;
            if ($declaration !== null) {
                return $declaration;
            }

            foreach ($this->parents[$key] ?? [] as $parent) {
                $queue[] = $parent;
            }
        }

        return null;
    }

    /**
     * Returns every recorded declaration in the order it was found.
     *
     * @return list<Declaration>
     */
    public function declarations(): array
    {
        return $this->declarations;
    }

    /**
     * Returns the lookup key of one member.
     */
    public function memberKey(string $className, string $memberName): string
    {
        return sprintf('%s::%s', strtolower($className), $memberName);
    }
}
