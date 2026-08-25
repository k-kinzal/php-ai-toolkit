<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ControlFlow;

use function array_keys;

use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Records what every class-like in the analysed code descends from.
 *
 * PHP has no `sealed`, so the classes below an interface or an abstract class are not
 * written down anywhere the type system can read. They are written down in the code itself,
 * and PHPStan runs rules a second time over what collectors gathered in the first pass.
 * That second pass is where the list exists, so the list is built here.
 *
 * The list reaches as far as the analysed paths and no further, which is the boundary
 * Kotlin draws around a sealed class and Java around a sealed interface: closed within the
 * module, nothing claimed about anyone else's code.
 *
 * @implements Collector<\PhpParser\Node\Stmt\ClassLike, array{name: string, instantiable: bool, ancestors: list<string>}>
 */
final class ClassAncestorCollector implements Collector
{
    /** @readonly */
    private ReflectionProvider $reflectionProvider;

    /**
     * Creates a collector that reads each declaration through the given reflection.
     */
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * @return class-string<\PhpParser\Node\Stmt\ClassLike>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\ClassLike::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassLike $node
     * @return array{name: string, instantiable: bool, ancestors: list<string>}|null
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): ?array
    {
        unset($scope);
        if (!isset($node->namespacedName)) {
            return null;
        }

        $name = $node->namespacedName->toString();
        if (!$this->reflectionProvider->hasClass($name)) {
            return null;
        }

        $class = $this->reflectionProvider->getClass($name);

        return [
            'name' => $class->getName(),
            'instantiable' => !$class->isAbstract() && !$class->isInterface() && !$class->isTrait(),
            'ancestors' => array_keys($class->getAncestors()),
        ];
    }
}
