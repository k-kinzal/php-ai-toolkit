<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Mutation;

use function array_map;

use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

use function sprintf;
use function strtolower;

use Toolkit\Mutation\MutationContractReader;
use Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser;

/**
 * Records callable declarations and their explicit mutation contracts.
 *
 * @phpstan-type MutationDeclaration array{key: string, symbol: string, line: int, parameters: list<array{name: string, variadic: bool}>, mutable: list<string>, this: bool, global: bool, problems: list<string>, prototypes: list<string>, static: bool, constructor: bool}
 * @implements Collector<\PhpParser\Node, MutationDeclaration>
 */
final class MutationDeclarationCollector implements Collector
{
    /** @readonly */
    private CallableId $ids;

    /** @readonly */
    private RulePhpDocParser $parser;

    /** @readonly */
    private MutationContractReader $reader;

    /**
     * Creates a declaration collector from syntax and identifier readers.
     */
    public function __construct(
        ?CallableId $ids = null,
        ?RulePhpDocParser $parser = null,
        ?MutationContractReader $reader = null,
    ) {
        $this->ids = $ids ?? new CallableId();
        $this->parser = $parser ?? new RulePhpDocParser();
        $this->reader = $reader ?? new MutationContractReader();
    }

    /**
     * @return class-string<\PhpParser\Node>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node::class;
    }

    /**
     * @return MutationDeclaration|null
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): ?array
    {
        if (!$node instanceof \PhpParser\Node\Stmt\Function_ && !$node instanceof \PhpParser\Node\Stmt\ClassMethod) {
            return null;
        }

        $declaration = $this->identity($node, $scope);
        if ($declaration === null) {
            return null;
        }

        $doc = $node->getDocComment();
        $contract = $this->reader->read($this->parser->parse($doc === null ? '/** */' : $doc->getText()));
        $parameters = [];
        foreach ($node->getParams() as $parameter) {
            $parameters[] = [
                'name' => $parameter->var instanceof \PhpParser\Node\Expr\Variable && is_string($parameter->var->name)
                    ? $parameter->var->name
                    : '',
                'variadic' => $parameter->variadic,
            ];
        }

        $problems = $contract->problems();
        $names = array_map(static fn (array $parameter): string => $parameter['name'], $parameters);
        foreach ($contract->mutableParameters() as $mutableParameter) {
            if (!in_array($mutableParameter, $names, true)) {
                $problems[] = sprintf('Remove +mut from unknown parameter $%s, or declare that parameter on %s.', $mutableParameter, $declaration['symbol']);
            }
        }

        $isStatic = $node instanceof \PhpParser\Node\Stmt\ClassMethod && $node->isStatic();
        if ($contract->mutatesThis() && (!$node instanceof \PhpParser\Node\Stmt\ClassMethod || $isStatic)) {
            $problems[] = 'Remove @mutation $this because only instance methods have a $this receiver.';
        }

        return $declaration + [
            'parameters' => $parameters,
            'mutable' => $contract->mutableParameters(),
            'this' => $contract->mutatesThis(),
            'global' => $contract->mutatesGlobal(),
            'problems' => $problems,
            'prototypes' => $node instanceof \PhpParser\Node\Stmt\ClassMethod ? $this->prototypes($node, $scope) : [],
            'static' => $isStatic,
            'constructor' => $node instanceof \PhpParser\Node\Stmt\ClassMethod && strtolower($node->name->toString()) === '__construct',
        ];
    }

    /**
     * @return array{key: string, symbol: string, line: int}|null
     */
    public function identity(\PhpParser\Node $node, Scope $scope): ?array
    {
        if ($node instanceof \PhpParser\Node\Stmt\Function_) {
            $name = isset($node->namespacedName) ? $node->namespacedName->toString() : $node->name->toString();

            return ['key' => $this->ids->function($name), 'symbol' => $name . '()', 'line' => $node->getStartLine()];
        }

        if (!$node instanceof \PhpParser\Node\Stmt\ClassMethod) {
            return null;
        }

        $class = $scope->getClassReflection();
        if ($class === null) {
            return null;
        }

        $method = $node->name->toString();

        return [
            'key' => $this->ids->method($class->getName(), $method),
            'symbol' => $class->getName() . '::' . $method . '()',
            'line' => $node->getStartLine(),
        ];
    }

    /**
     * @return list<string>
     */
    public function prototypes(\PhpParser\Node\Stmt\ClassMethod $node, Scope $scope): array
    {
        $class = $scope->getClassReflection();
        if ($class === null) {
            return [];
        }

        $method = $node->name->toString();
        $prototypes = [];
        foreach ($class->getAncestors() as $ancestor) {
            if ($ancestor->getName() !== $class->getName() && $ancestor->hasNativeMethod($method)) {
                $prototypes[] = $this->ids->method($ancestor->getName(), $method);
            }
        }

        return $prototypes;
    }
}
