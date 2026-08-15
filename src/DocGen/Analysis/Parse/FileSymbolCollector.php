<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Parse;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;

/**
 * Collects all documented symbols from one parsed source file.
 */
final class FileSymbolCollector
{
    /** @readonly */
    private UseMapCollector $useMapCollector;

    /** @readonly */
    private ClassLikeBuilder $classLikeBuilder;

    /** @readonly */
    private FunctionBuilder $functionBuilder;

    /**
     * Creates a file symbol collector from builder collaborators.
     */
    public function __construct(
        ?UseMapCollector $useMapCollector = null,
        ?ClassLikeBuilder $classLikeBuilder = null,
        ?FunctionBuilder $functionBuilder = null,
    ) {
        $this->useMapCollector = $useMapCollector ?? new UseMapCollector();
        $this->classLikeBuilder = $classLikeBuilder ?? new ClassLikeBuilder();
        $this->functionBuilder = $functionBuilder ?? new FunctionBuilder();
    }

    /**
     * Collects the class-like and function symbols of a parsed file.
     *
     * @param list<Stmt> $statements
     */
    public function collect(array $statements, string $packageName, string $file, bool $isDev): FileSymbols
    {
        $classLikes = [];
        $functions = [];
        foreach ($this->namespaceGroups($statements) as $group) {
            $context = new SymbolContext(
                $group['namespace'],
                $this->useMapCollector->collect($group['statements']),
                $packageName,
                $file,
                $isDev,
            );
            foreach ($group['statements'] as $statement) {
                if ($statement instanceof ClassLike) {
                    $classLike = $this->classLikeBuilder->build($statement, $context);
                    if ($classLike !== null) {
                        $classLikes[] = $classLike;
                    }
                }

                if ($statement instanceof Function_) {
                    $functions[] = $this->functionBuilder->build($statement, $context);
                }
            }
        }

        return new FileSymbols($classLikes, $functions);
    }

    /**
     * Groups top-level statements by their containing namespace.
     *
     * @param list<Stmt> $statements
     *
     * @return list<array{namespace: string, statements: array<Stmt>}>
     */
    public function namespaceGroups(array $statements): array
    {
        $groups = [];
        $global = [];
        foreach ($statements as $statement) {
            if ($statement instanceof Namespace_) {
                $groups[] = [
                    'namespace' => $statement->name !== null ? $statement->name->toString() : '',
                    'statements' => $statement->stmts,
                ];
            } else {
                $global[] = $statement;
            }
        }

        if ($global !== []) {
            $groups[] = ['namespace' => '', 'statements' => $global];
        }

        return $groups;
    }
}
