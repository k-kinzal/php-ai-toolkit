<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Reference;

use function array_key_last;
use function array_pop;
use function is_string;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\UnionType;
use PhpParser\NodeVisitor;

use function strtolower;

/**
 * AST visitor that records references to documented symbols.
 *
 * Method call receivers are resolved only from statically certain sources,
 * namely $this, typed parameters, typed properties, and direct construction,
 * so the reported call sites contain no guesses.
 */
final class UsageCollector implements NodeVisitor
{
    /** @readonly */
    private PropertyTypeScanner $propertyScanner;

    private string $file = '';

    private bool $fromDev = false;

    /** @var list<Usage> */
    private array $usages = [];

    /** @var list<array{fqcn: string, parent: ?string, props: array<string, string>}> */
    private array $classStack = [];

    /** @var list<LocalTypeMap> */
    private array $scopeStack = [];

    /** @var list<?string> */
    private array $memberStack = [];

    /**
     * Creates a usage collector from its property scanning collaborator.
     */
    public function __construct(?PropertyTypeScanner $propertyScanner = null)
    {
        $this->propertyScanner = $propertyScanner ?? new PropertyTypeScanner();
    }

    /**
     * Starts collecting for one source file, resetting all scope state.
     */
    public function beginFile(string $file, bool $fromDev): void
    {
        $this->file = $file;
        $this->fromDev = $fromDev;
        $this->classStack = [];
        $this->scopeStack = [];
        $this->memberStack = [];
    }

    /**
     * Returns all usages collected so far.
     *
     * @return list<Usage>
     */
    public function usages(): array
    {
        return $this->usages;
    }

    /**
     * Keeps the node list unchanged before traversal.
     *
     * @param array<Node> $nodes
     *
     * @return null
     */
    public function beforeTraverse(array $nodes)
    {
        return null;
    }

    /**
     * Keeps the node list unchanged after traversal.
     *
     * @param array<Node> $nodes
     *
     * @return null
     */
    public function afterTraverse(array $nodes)
    {
        return null;
    }

    /**
     * Records references and scope changes for one entered node.
     *
     * @return null
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof ClassLike) {
            $this->enterClassLike($node);
        } elseif ($node instanceof ClassMethod || $node instanceof Function_) {
            $this->enterFunctionLike($node);
        } elseif ($node instanceof Closure || $node instanceof ArrowFunction) {
            $this->enterClosure($node);
        } elseif ($node instanceof TraitUse) {
            foreach ($node->traits as $name) {
                $this->record($name->toString(), null, 'use-trait', $node->getStartLine());
            }
        } elseif ($node instanceof Property) {
            foreach ($this->typeNames($node->type) as $name) {
                $this->record($name->toString(), null, 'type', $node->getStartLine());
            }
        } elseif ($node instanceof Expr) {
            $this->enterExpression($node);
        } elseif ($node instanceof Attribute) {
            $this->record($node->name->toString(), null, 'attribute', $node->getStartLine());
        }

        return null;
    }

    /**
     * Records references produced by one expression node.
     */
    public function enterExpression(Expr $node): void
    {
        if ($node instanceof Assign) {
            $this->trackAssignment($node);
        } elseif ($node instanceof New_ && $node->class instanceof Name) {
            $this->record($node->class->toString(), null, 'new', $node->getStartLine());
        } elseif ($node instanceof FuncCall && $node->name instanceof Name) {
            $this->record($node->name->toString(), null, 'function-call', $node->getStartLine());
        } elseif ($node instanceof MethodCall) {
            $this->recordMethodCall($node);
        } elseif ($node instanceof StaticCall) {
            $this->recordStaticCall($node);
        } elseif ($node instanceof ClassConstFetch && $node->class instanceof Name && $node->name instanceof Identifier) {
            $target = $this->resolveClassRef($node->class->toString());
            if ($target !== null) {
                $this->record($target, $node->name->toString(), 'class-const', $node->getStartLine());
            }
        } elseif ($node instanceof Instanceof_ && $node->class instanceof Name) {
            $this->record($node->class->toString(), null, 'instanceof', $node->getStartLine());
        }
    }

    /**
     * Pops scope state for one left node.
     *
     * @return null
     */
    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassLike) {
            array_pop($this->classStack);
        } elseif ($node instanceof ClassMethod || $node instanceof Function_ || $node instanceof Closure || $node instanceof ArrowFunction) {
            array_pop($this->scopeStack);
            array_pop($this->memberStack);
        }

        return null;
    }

    /**
     * Pushes class scope and records inheritance references.
     */
    public function enterClassLike(ClassLike $node): void
    {
        $fqcn = $node->namespacedName !== null ? $node->namespacedName->toString() : '';
        $parent = null;
        if ($node instanceof Class_ && $node->extends !== null) {
            $parent = $node->extends->toString();
        }

        $this->classStack[] = ['fqcn' => $fqcn, 'parent' => $parent, 'props' => $this->propertyScanner->scan($node)];
        if ($parent !== null) {
            $this->record($parent, null, 'extends', $node->getStartLine());
        }

        if ($node instanceof Class_ || $node instanceof Node\Stmt\Enum_) {
            foreach ($node->implements as $name) {
                $this->record($name->toString(), null, 'implements', $node->getStartLine());
            }
        }

        if ($node instanceof Node\Stmt\Interface_) {
            foreach ($node->extends as $name) {
                $this->record($name->toString(), null, 'extends', $node->getStartLine());
            }
        }
    }


    /**
     * Pushes a new local scope seeded with the typed parameters.
     *
     * @param ClassMethod|Function_ $node
     */
    public function enterFunctionLike(Node $node): void
    {
        $scope = new LocalTypeMap();
        foreach ($node->params as $param) {
            $this->registerParam($scope, $param);
        }

        foreach ($this->typeNames($node->returnType) as $name) {
            $this->record($name->toString(), null, 'type', $node->getStartLine());
        }

        $this->scopeStack[] = $scope;
        $this->memberStack[] = $node->name->toString();
    }

    /**
     * Pushes a closure scope that inherits the enclosing variable types.
     *
     * @param Closure|ArrowFunction $node
     */
    public function enterClosure(Node $node): void
    {
        $scope = new LocalTypeMap();
        $current = $this->scopeStack[array_key_last($this->scopeStack) ?? 0] ?? null;
        if ($current !== null) {
            foreach ($current->all() as $variable => $type) {
                $scope->set($variable, $type);
            }
        }

        foreach ($node->params as $param) {
            $this->registerParam($scope, $param);
        }

        $this->scopeStack[] = $scope;
        $this->memberStack[] = $this->memberStack[array_key_last($this->memberStack) ?? 0] ?? null;
    }

    /**
     * Registers one parameter's type into a scope and records type usages.
     */
    public function registerParam(LocalTypeMap $scope, Param $param): void
    {
        foreach ($this->typeNames($param->type) as $name) {
            $this->record($name->toString(), null, 'type', $param->getStartLine());
        }

        if ($param->type instanceof Name && $param->var instanceof Variable && is_string($param->var->name)) {
            $scope->set($param->var->name, $param->type->toString());
        }
    }

    /**
     * Tracks variable types through direct construction assignments.
     */
    public function trackAssignment(Assign $node): void
    {
        if (!$node->var instanceof Variable || !is_string($node->var->name)) {
            return;
        }

        $scope = $this->scopeStack[array_key_last($this->scopeStack) ?? 0] ?? null;
        if ($scope === null) {
            return;
        }

        if ($node->expr instanceof New_ && $node->expr->class instanceof Name) {
            $scope->set($node->var->name, $node->expr->class->toString());
        } else {
            $inferred = $this->receiverType($node->expr);
            if ($inferred !== null) {
                $scope->set($node->var->name, $inferred);
            } else {
                $scope->forget($node->var->name);
            }
        }
    }

    /**
     * Resolves the class type of a call receiver expression, if certain.
     */
    public function receiverType(Expr $expr): ?string
    {
        if ($expr instanceof Variable && is_string($expr->name)) {
            return $this->variableType($expr->name);
        }

        if ($expr instanceof PropertyFetch && $expr->var instanceof Variable && $expr->var->name === 'this' && $expr->name instanceof Identifier) {
            $current = $this->classStack[array_key_last($this->classStack) ?? 0] ?? null;

            return $current !== null ? ($current['props'][strtolower($expr->name->toString())] ?? null) : null;
        }

        if ($expr instanceof New_ && $expr->class instanceof Name) {
            return $expr->class->toString();
        }

        return null;
    }

    /**
     * Resolves the class type of a plain variable in the current scope.
     */
    public function variableType(string $name): ?string
    {
        if ($name === 'this') {
            $current = $this->classStack[array_key_last($this->classStack) ?? 0] ?? null;

            return $current !== null && $current['fqcn'] !== '' ? $current['fqcn'] : null;
        }

        $scope = $this->scopeStack[array_key_last($this->scopeStack) ?? 0] ?? null;

        return $scope !== null ? $scope->typeOf($name) : null;
    }

    /**
     * Records a method call whose receiver type is statically known.
     */
    public function recordMethodCall(MethodCall $node): void
    {
        if (!$node->name instanceof Identifier) {
            return;
        }

        $receiver = $this->receiverType($node->var);
        if ($receiver !== null) {
            $this->record($receiver, $node->name->toString(), 'method-call', $node->getStartLine());
        }
    }

    /**
     * Records a static method call, resolving self, static, and parent.
     */
    public function recordStaticCall(StaticCall $node): void
    {
        if (!$node->class instanceof Name || !$node->name instanceof Identifier) {
            return;
        }

        $target = $this->resolveClassRef($node->class->toString());
        if ($target !== null) {
            $this->record($target, $node->name->toString(), 'static-call', $node->getStartLine());
        }
    }

    /**
     * Resolves a class reference, mapping self and static to the current class.
     */
    public function resolveClassRef(string $name): ?string
    {
        $lower = strtolower($name);
        if ($lower === 'self' || $lower === 'static') {
            $current = $this->classStack[array_key_last($this->classStack) ?? 0] ?? null;

            return $current !== null && $current['fqcn'] !== '' ? $current['fqcn'] : null;
        }

        if ($lower === 'parent') {
            $current = $this->classStack[array_key_last($this->classStack) ?? 0] ?? null;

            return $current !== null ? $current['parent'] : null;
        }

        return $name;
    }

    /**
     * Extracts the class names referenced by a native type node.
     *
     * @return list<Name>
     */
    public function typeNames(?Node $node): array
    {
        if ($node instanceof Name) {
            return [$node];
        }

        if ($node instanceof NullableType) {
            return $this->typeNames($node->type);
        }

        if ($node instanceof UnionType || $node instanceof IntersectionType) {
            $names = [];
            foreach ($node->types as $type) {
                foreach ($this->typeNames($type) as $name) {
                    $names[] = $name;
                }
            }

            return $names;
        }

        return [];
    }

    /**
     * Appends one usage with the current scope as its origin.
     */
    public function record(string $targetFqcn, ?string $member, string $kind, int $line): void
    {
        $current = $this->classStack[array_key_last($this->classStack) ?? 0] ?? null;

        $this->usages[] = new Usage(
            $targetFqcn,
            $member,
            $kind,
            $current !== null && $current['fqcn'] !== '' ? $current['fqcn'] : null,
            $this->memberStack[array_key_last($this->memberStack) ?? 0] ?? null,
            $this->file,
            $line,
            $this->fromDev,
        );
    }
}
