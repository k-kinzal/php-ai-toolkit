<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Mutation;

use function is_string;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\Clone_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PostDec;
use PhpParser\Node\Expr\PostInc;
use PhpParser\Node\Expr\PreDec;
use PhpParser\Node\Expr\PreInc;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Global_;
use PhpParser\Node\Stmt\Static_;
use PhpParser\Node\Stmt\Unset_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ClassReflection;
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
     * @return class-string<Node>
     */
    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @return list<Operation>|null
     */
    public function processNode(Node $node, Scope $scope): ?array
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
    public function records(Node $node, Scope $scope, string $caller, int $line): array
    {
        $persistentAliases = $this->persistentAliases($node, $caller, $line);
        if ($persistentAliases !== null) {
            return $persistentAliases;
        }

        if ($node instanceof Unset_) {
            return $this->mutations($node->vars, $caller, $line);
        }

        $assignment = $this->assignment($node, $caller, $line);
        if ($assignment !== null) {
            return $assignment;
        }

        if ($node instanceof AssignOp
            || $node instanceof PreInc
            || $node instanceof PreDec
            || $node instanceof PostInc
            || $node instanceof PostDec) {
            return [['kind' => 'mutation', 'caller' => $caller, 'line' => $line, 'root' => $this->root($node->var)]];
        }

        if ($node instanceof FuncCall
            || $node instanceof MethodCall
            || $node instanceof NullsafeMethodCall
            || $node instanceof StaticCall
            || $node instanceof New_) {
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
    public function persistentAliases(Node $node, string $caller, int $line): ?array
    {
        if (!$node instanceof Global_ && !$node instanceof Static_) {
            return null;
        }

        $variables = [];
        if ($node instanceof Global_) {
            $variables = $node->vars;
        } else {
            foreach ($node->vars as $staticVariable) {
                $variables[] = $staticVariable->var;
            }
        }
        $records = [];
        foreach ($variables as $variable) {
            if ($variable instanceof Variable && is_string($variable->name)) {
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
    public function assignment(Node $node, string $caller, int $line): ?array
    {
        if (!$node instanceof Assign && !$node instanceof AssignRef) {
            return null;
        }

        $records = [['kind' => 'mutation', 'caller' => $caller, 'line' => $line, 'root' => $this->root($node->var)]];
        if ($node->var instanceof Variable && is_string($node->var->name)) {
            $records[] = ['kind' => 'alias', 'caller' => $caller, 'line' => $line, 'variable' => $node->var->name, 'root' => $this->root($node->expr)];
        }

        return $records;
    }

    /**
     * @param array<Expr> $expressions
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
     * @param FuncCall|MethodCall|NullsafeMethodCall|StaticCall|New_ $node
     * @return CallOperation|null
     */
    public function call(Expr $node, Scope $scope, string $caller, int $line): ?array
    {
        $callees = [];
        $receiver = 'local';
        if ($node instanceof FuncCall && $node->name instanceof Name) {
            $functionName = $this->reflectionProvider->resolveFunctionName($node->name, $scope);
            if ($functionName !== null) {
                $callees[] = $this->ids->function($functionName);
            }
        } elseif (($node instanceof MethodCall || $node instanceof NullsafeMethodCall)
            && $node->name instanceof Identifier) {
            $receiver = $this->root($node->var);
            $callees = $this->methodCallees($scope->getType($node->var)->getObjectClassReflections(), $node->name->toString(), $scope);
        } elseif ($node instanceof StaticCall && $node->name instanceof Identifier) {
            $type = $node->class instanceof Name
                ? $scope->resolveTypeByName($node->class)
                : $scope->getType($node->class);
            $callees = $this->methodCallees($type->getObjectClassReflections(), $node->name->toString(), $scope);
        } elseif ($node instanceof New_ && $node->class instanceof Name) {
            $callees = $this->methodCallees($scope->getType($node)->getObjectClassReflections(), '__construct', $scope);
        }

        if ($callees === []) {
            return null;
        }

        $arguments = [];
        foreach ($node->getArgs() as $index => $argument) {
            $arguments[] = [
                'index' => $index,
                'name' => $argument->name instanceof Identifier ? $argument->name->toString() : null,
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
     * @param list<ClassReflection> $classes
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
    public function root(Node $expression): string
    {
        if ($expression instanceof Variable && is_string($expression->name)) {
            if ($expression->name === 'this') {
                return 'this';
            }

            if (in_array(strtolower($expression->name), self::SUPERGLOBALS, true)) {
                return 'global';
            }

            return 'var:' . $expression->name;
        }

        if ($expression instanceof ArrayDimFetch
            || $expression instanceof PropertyFetch
            || $expression instanceof NullsafePropertyFetch) {
            return $this->root($expression->var);
        }

        if ($expression instanceof StaticPropertyFetch) {
            return 'global';
        }

        if ($expression instanceof Clone_) {
            return $this->root($expression->expr);
        }

        return $expression instanceof New_ ? 'local' : 'unknown';
    }

    /** @var list<string> */
    private const SUPERGLOBALS = ['globals', '_server', '_get', '_post', '_files', '_cookie', '_session', '_request', '_env'];
}
