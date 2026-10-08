<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Architecture\Dependency;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Resolves callable declarations without treating similarly named calls as equivalent.
 */
final class DependencyCallResolver
{
    /**
     * Creates a resolver using PHPStan's symbol discovery.
     */
    public function __construct(private ReflectionProvider $reflections)
    {
    }

    /**
     * Resolves functions, methods, and constructors with a definite receiver.
     */
    public function resolve(Node\Expr\CallLike $node, Scope $scope): FunctionReflection|MethodReflection|null
    {
        if ($node instanceof Node\Expr\FuncCall) {
            $name = $node->name instanceof Node\Name ? $node->name : null;
            if ($name === null) {
                return null;
            }

            return $this->reflections->hasFunction($name, $scope) ? $this->reflections->getFunction($name, $scope) : null;
        }
        if ($node instanceof Node\Expr\New_) {
            $class = $this->className($node->class, $scope);
            $method = '__construct';
        } elseif ($node instanceof Node\Expr\StaticCall) {
            $class = $this->className($node->class, $scope);
            $method = $node->name instanceof Node\Identifier ? $node->name->toString() : null;
        } elseif ($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\NullsafeMethodCall) {
            $names = $scope->getType($node->var)->getObjectClassNames();
            $class = count($names) === 1 ? $names[0] : null;
            $method = $node->name instanceof Node\Identifier ? $node->name->toString() : null;
        } else {
            return null;
        }

        if ($class === null || $method === null || !$this->reflections->hasClass($class)) {
            return null;
        }
        $reflection = $this->reflections->getClass($class);

        return $reflection->hasMethod($method) ? $reflection->getMethod($method, $scope) : null;
    }

    /**
     * Resolves a written class name or one statically known class-string.
     */
    public function className(Node $node, Scope $scope): ?string
    {
        if ($node instanceof Node\Name) {
            return $scope->resolveName($node);
        }
        if (!$node instanceof Node\Expr) {
            return null;
        }
        $type = $scope->getType($node);
        $strings = $type->getConstantStrings();

        return $type->isConstantScalarValue()->yes() && count($strings) === 1 ? $strings[0]->getValue() : null;
    }

    /**
     * Returns a fully qualified callable name suitable for reader registration.
     */
    public function name(FunctionReflection|MethodReflection $reflection): string
    {
        return $reflection instanceof MethodReflection
            ? $reflection->getDeclaringClass()->getName() . '::' . $reflection->getName()
            : $reflection->getName();
    }

    /**
     * Reads positional or reordered named arguments without guessing unpacked values.
     */
    public function argument(Node\Expr\CallLike $node, int $position, string $name): ?Node\Expr
    {
        if ($node->isFirstClassCallable()) {
            return null;
        }
        foreach ($node->getArgs() as $index => $argument) {
            if ($argument->unpack) {
                return null;
            }
            if ($argument->name !== null) {
                if ($argument->name->toString() === $name) {
                    return $argument->value;
                }
            } elseif ($index === $position) {
                return $argument->value;
            }
        }

        return null;
    }
}
