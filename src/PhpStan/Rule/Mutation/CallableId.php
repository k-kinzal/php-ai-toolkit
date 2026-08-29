<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Mutation;

use PHPStan\Analyser\Scope;

use function str_contains;
use function strtolower;

/**
 * Gives declarations and body nodes the same stable callable identifier.
 */
final class CallableId
{
    /**
     * Returns the named callable containing the current node.
     */
    public function current(Scope $scope): ?string
    {
        $function = $scope->getFunction();
        if ($function === null || str_contains($function->getName(), '{closure}')) {
            return null;
        }

        $class = $scope->getClassReflection();

        $name = $function->getName();
        $namespace = $scope->getNamespace();
        if ($class === null && $namespace !== null && !str_contains($name, '\\')) {
            $name = $namespace . '\\' . $name;
        }

        return $class === null
            ? $this->function($name)
            : $this->method($class->getName(), $name);
    }

    /**
     * Builds the identifier of a top-level function.
     */
    public function function(string $name): string
    {
        return 'function:' . strtolower(ltrim($name, '\\'));
    }

    /**
     * Builds the identifier of a method declaration.
     */
    public function method(string $className, string $methodName): string
    {
        return 'method:' . strtolower(ltrim($className, '\\') . '::' . $methodName);
    }
}
