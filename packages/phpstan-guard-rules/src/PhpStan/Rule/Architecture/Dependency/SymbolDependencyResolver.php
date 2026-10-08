<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Architecture\Dependency;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use Toolkit\PhpStan\Rule\Visibility\VisibilityReferenceCollector;

/**
 * Converts written type references and resolved calls into declaration-file dependencies.
 */
final class SymbolDependencyResolver
{
    private VisibilityReferenceCollector $references;

    /**
     * Creates a resolver using the same native type references as visibility checks.
     */
    public function __construct(private ReflectionProvider $reflections, private DependencyCallResolver $calls)
    {
        $this->references = new VisibilityReferenceCollector();
    }

    /**
     * Returns declaration files mapped to the symbols referenced by one node.
     *
     * @return array<string, string>
     */
    public function resolve(Node $node, Scope $scope): array
    {
        $targets = [];
        foreach ($this->references->fromNode($node, $scope) as $reference) {
            $targets += $this->classTarget($reference['className']);
        }
        if ($node instanceof Node\Expr\CallLike) {
            $call = $this->calls->resolve($node, $scope);
            if ($call !== null) {
                $file = $call instanceof MethodReflection ? $call->getDeclaringClass()->getFileName() : $call->getFileName();
                if ($file !== null) {
                    $targets[$file] = $this->calls->name($call) . '()';
                }
            }
        }
        if ($node instanceof Node\Expr\ConstFetch && $this->reflections->hasConstant($node->name, $scope)) {
            $constant = $this->reflections->getConstant($node->name, $scope);
            $file = $constant->getFileName();
            if ($file !== null) {
                $targets[$file] = $constant->getName();
            }
        }

        return $targets + $this->expressionTargets($node, $scope);
    }

    /**
     * Resolves instance property receivers and dynamic class references.
     *
     * @return array<string, string>
     */
    public function expressionTargets(Node $node, Scope $scope): array
    {
        if ($node instanceof Node\Expr\PropertyFetch || $node instanceof Node\Expr\NullsafePropertyFetch) {
            $names = $scope->getType($node->var)->getObjectClassNames();

            return count($names) === 1 ? $this->classTarget($names[0]) : [];
        }
        if ($node instanceof Node\Expr\New_ || $node instanceof Node\Expr\StaticCall
            || $node instanceof Node\Expr\StaticPropertyFetch || $node instanceof Node\Expr\ClassConstFetch
            || $node instanceof Node\Expr\Instanceof_) {
            $name = $this->calls->className($node->class, $scope);

            return $name !== null ? $this->classTarget($name) : [];
        }

        return [];
    }

    /**
     * Resolves a class, interface, trait, or enum to its declaration file.
     *
     * @return array<string, string>
     */
    public function classTarget(string $name): array
    {
        if (!$this->reflections->hasClass($name)) {
            return [];
        }
        $file = $this->reflections->getClass($name)->getFileName();

        return $file !== null ? [$file => $name] : [];
    }
}
