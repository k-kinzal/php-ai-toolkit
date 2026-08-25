<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Reference;

use function ltrim;
use function strtolower;

use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\FunctionDoc;

/**
 * Case-insensitive lookup table of all documented symbols.
 *
 * The first registration of a name wins, which deduplicates symbols that
 * are reachable through more than one package autoload map.
 */
final class SymbolTable
{
    /** @var array<string, ClassLikeDoc> */
    private array $classLikes = [];

    /** @var array<string, FunctionDoc> */
    private array $functions = [];

    /**
     * Registers a class-like symbol unless its name is already taken.
     */
    public function registerClassLike(ClassLikeDoc $classLike): void
    {
        $key = strtolower($classLike->fqcn);
        if (!isset($this->classLikes[$key])) {
            $this->classLikes[$key] = $classLike;
        }
    }

    /**
     * Registers a function symbol unless its name is already taken.
     */
    public function registerFunction(FunctionDoc $function): void
    {
        $key = strtolower($function->fqn);
        if (!isset($this->functions[$key])) {
            $this->functions[$key] = $function;
        }
    }

    /**
     * Looks up a class-like symbol by fully qualified name.
     */
    public function classLike(string $fqcn): ?ClassLikeDoc
    {
        return $this->classLikes[strtolower(ltrim($fqcn, '\\'))] ?? null;
    }

    /**
     * Looks up a function symbol by fully qualified name.
     */
    public function functionNamed(string $fqn): ?FunctionDoc
    {
        return $this->functions[strtolower(ltrim($fqn, '\\'))] ?? null;
    }
}
