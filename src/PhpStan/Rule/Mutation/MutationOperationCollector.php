<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Mutation;

use function is_string;

use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ReflectionProvider;

use function strtolower;

/**
 * Records writes, aliases, and statically resolved calls inside callables.
 *
 * @phpstan-type AliasOperation array{kind: 'alias', caller: string, line: int, variable: string, root: string}
 * @phpstan-type MutationOperation array{kind: 'mutation', caller: string, line: int, root: string}
 * @phpstan-type CallOperation array{kind: 'call', caller: string, line: int, callees: list<string>, receiver: string, arguments: list<array{index: int, name: ?string, root: string}>}
 * @phpstan-type Operation AliasOperation|MutationOperation|CallOperation
 * @implements Collector<\PhpParser\Node, list<Operation>>
 */
final class MutationOperationCollector implements Collector
{
    /** @readonly */
    private CallableId $ids;

    /** @readonly */
    private ReflectionProvider $reflectionProvider;

    /**
     * Creates an operation collector from the shared callable identifier.
     */
    public function __construct(ReflectionProvider $reflectionProvider, ?CallableId $ids = null)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->ids = $ids ?? new CallableId();
    }

    /**
     * @return class-string<\PhpParser\Node>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node::class;
    }

    /**
     * @return list<Operation>|null
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): ?array
    {
        $caller = $this->ids->current($scope);
        if ($caller === null) {
            return null;
        }

        $records = $this->records($node, $scope, $caller, $node->getStartLine());

        return $records === [] ? null : $records;
    }

    /**
     * @return list<Operation>
     */
    public function records(\PhpParser\Node $node, Scope $scope, string $caller, int $line): array
    {
        $persistentAliases = $this->persistentAliases($node, $caller, $line);
        if ($persistentAliases !== null) {
            return $persistentAliases;
        }

        if ($node instanceof \PhpParser\Node\Stmt\Unset_) {
            return $this->mutations($node->vars, $caller, $line);
        }

        $assignment = $this->assignment($node, $caller, $line);
        if ($assignment !== null) {
            return $assignment;
        }

        if ($node instanceof \PhpParser\Node\Expr\AssignOp
            || $node instanceof \PhpParser\Node\Expr\PreInc
            || $node instanceof \PhpParser\Node\Expr\PreDec
            || $node instanceof \PhpParser\Node\Expr\PostInc
            || $node instanceof \PhpParser\Node\Expr\PostDec) {
            return [['kind' => 'mutation', 'caller' => $caller, 'line' => $line, 'root' => $this->root($node->var)]];
        }

        if ($node instanceof \PhpParser\Node\Expr\FuncCall
            || $node instanceof \PhpParser\Node\Expr\MethodCall
            || $node instanceof \PhpParser\Node\Expr\NullsafeMethodCall
            || $node instanceof \PhpParser\Node\Expr\StaticCall
            || $node instanceof \PhpParser\Node\Expr\New_) {
            $call = $this->call($node, $scope, $caller, $line);

            return $call === null ? [] : [$call];
        }

        return [];
    }

    /**
     * Treats imported globals and function-static variables as persistent state.
     *
     * @return list<AliasOperation>|null
     */
    public function persistentAliases(\PhpParser\Node $node, string $caller, int $line): ?array
    {
        if (!$node instanceof \PhpParser\Node\Stmt\Global_ && !$node instanceof \PhpParser\Node\Stmt\Static_) {
            return null;
        }

        $variables = [];
        if ($node instanceof \PhpParser\Node\Stmt\Global_) {
            $variables = $node->vars;
        } else {
            foreach ($node->vars as $staticVariable) {
                $variables[] = $staticVariable->var;
            }
        }
        $records = [];
        foreach ($variables as $variable) {
            if ($variable instanceof \PhpParser\Node\Expr\Variable && is_string($variable->name)) {
                $records[] = ['kind' => 'alias', 'caller' => $caller, 'line' => $line, 'variable' => $variable->name, 'root' => 'global'];
            }
        }

        return $records;
    }

    /**
     * Records a plain or reference assignment and its local alias edge.
     *
     * @return list<Operation>|null
     */
    public function assignment(\PhpParser\Node $node, string $caller, int $line): ?array
    {
        if (!$node instanceof \PhpParser\Node\Expr\Assign && !$node instanceof \PhpParser\Node\Expr\AssignRef) {
            return null;
        }

        $records = [['kind' => 'mutation', 'caller' => $caller, 'line' => $line, 'root' => $this->root($node->var)]];
        if ($node->var instanceof \PhpParser\Node\Expr\Variable && is_string($node->var->name)) {
            $records[] = ['kind' => 'alias', 'caller' => $caller, 'line' => $line, 'variable' => $node->var->name, 'root' => $this->root($node->expr)];
        }

        return $records;
    }

    /**
     * @param array<\PhpParser\Node\Expr> $expressions
     * @return list<MutationOperation>
     */
    public function mutations(array $expressions, string $caller, int $line): array
    {
        $records = [];
        foreach ($expressions as $expression) {
            $records[] = ['kind' => 'mutation', 'caller' => $caller, 'line' => $line, 'root' => $this->root($expression)];
        }

        return $records;
    }

    /**
     * @param \PhpParser\Node\Expr\FuncCall|\PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\NullsafeMethodCall|\PhpParser\Node\Expr\StaticCall|\PhpParser\Node\Expr\New_ $node
     * @return CallOperation|null
     */
    public function call(\PhpParser\Node\Expr $node, Scope $scope, string $caller, int $line): ?array
    {
        $callees = [];
        $receiver = 'local';
        if ($node instanceof \PhpParser\Node\Expr\FuncCall && $node->name instanceof \PhpParser\Node\Name) {
            $functionName = $this->reflectionProvider->resolveFunctionName($node->name, $scope);
            if ($functionName !== null) {
                $callees[] = $this->ids->function($functionName);
            }
        } elseif (($node instanceof \PhpParser\Node\Expr\MethodCall || $node instanceof \PhpParser\Node\Expr\NullsafeMethodCall)
            && $node->name instanceof \PhpParser\Node\Identifier) {
            $receiver = $this->root($node->var);
            $callees = $this->methodCallees($scope->getType($node->var)->getObjectClassReflections(), $node->name->toString(), $scope);
        } elseif ($node instanceof \PhpParser\Node\Expr\StaticCall && $node->name instanceof \PhpParser\Node\Identifier) {
            $type = $node->class instanceof \PhpParser\Node\Name
                ? $scope->resolveTypeByName($node->class)
                : $scope->getType($node->class);
            $callees = $this->methodCallees($type->getObjectClassReflections(), $node->name->toString(), $scope);
        } elseif ($node instanceof \PhpParser\Node\Expr\New_ && $node->class instanceof \PhpParser\Node\Name) {
            $callees = $this->methodCallees($scope->getType($node)->getObjectClassReflections(), '__construct', $scope);
        }

        if ($callees === []) {
            return null;
        }

        $arguments = [];
        foreach ($node->getArgs() as $index => $argument) {
            $arguments[] = [
                'index' => $index,
                'name' => $argument->name instanceof \PhpParser\Node\Identifier ? $argument->name->toString() : null,
                'root' => $this->root($argument->value),
            ];
        }

        return [
            'kind' => 'call',
            'caller' => $caller,
            'line' => $line,
            'callees' => $callees,
            'receiver' => $receiver,
            'arguments' => $arguments,
        ];
    }

    /**
     * @param list<\PHPStan\Reflection\ClassReflection> $classes
     * @return list<string>
     */
    public function methodCallees(array $classes, string $method, Scope $scope): array
    {
        $callees = [];
        foreach ($classes as $class) {
            $declaringClass = $class->hasMethod($method)
                ? $class->getMethod($method, $scope)->getDeclaringClass()->getName()
                : $class->getName();
            $callees[$this->ids->method($declaringClass, $method)] = true;
        }

        return array_keys($callees);
    }

    /**
     * Reduces an expression to its externally visible mutation root.
     */
    public function root(\PhpParser\Node $expression): string
    {
        if ($expression instanceof \PhpParser\Node\Expr\Variable && is_string($expression->name)) {
            if ($expression->name === 'this') {
                return 'this';
            }

            if (in_array(strtolower($expression->name), self::SUPERGLOBALS, true)) {
                return 'global';
            }

            return 'var:' . $expression->name;
        }

        if ($expression instanceof \PhpParser\Node\Expr\ArrayDimFetch
            || $expression instanceof \PhpParser\Node\Expr\PropertyFetch
            || $expression instanceof \PhpParser\Node\Expr\NullsafePropertyFetch) {
            return $this->root($expression->var);
        }

        if ($expression instanceof \PhpParser\Node\Expr\StaticPropertyFetch) {
            return 'global';
        }

        if ($expression instanceof \PhpParser\Node\Expr\Clone_) {
            return $this->root($expression->expr);
        }

        return $expression instanceof \PhpParser\Node\Expr\New_ ? 'local' : 'unknown';
    }

    /** @var list<string> */
    private const SUPERGLOBALS = ['globals', '_server', '_get', '_post', '_files', '_cookie', '_session', '_request', '_env'];
}
