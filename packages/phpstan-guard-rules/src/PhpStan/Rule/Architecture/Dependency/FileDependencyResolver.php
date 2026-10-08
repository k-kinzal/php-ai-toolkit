<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Architecture\Dependency;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * Resolves include expressions and registered file-reader arguments to local paths.
 */
final class FileDependencyResolver
{
    /** @var array<string, array{position: int, name: string}> */
    private const READERS = [
        'file_get_contents' => ['position' => 0, 'name' => 'filename'],
        'fopen' => ['position' => 0, 'name' => 'filename'],
        'file' => ['position' => 0, 'name' => 'filename'],
        'readfile' => ['position' => 0, 'name' => 'filename'],
        'parse_ini_file' => ['position' => 0, 'name' => 'filename'],
        'simplexml_load_file' => ['position' => 0, 'name' => 'filename'],
        'hash_file' => ['position' => 1, 'name' => 'filename'],
        'md5_file' => ['position' => 0, 'name' => 'filename'],
        'sha1_file' => ['position' => 0, 'name' => 'filename'],
        'splfileobject::__construct' => ['position' => 0, 'name' => 'filename'],
    ];

    /** @var array<string, array{position: int, name: string}> */
    private array $readers;

    private DependencyPathExpression $expressions;

    /**
     * @param array<string, array{position: int, name: string}> $readers additional or replacement reader definitions
     */
    public function __construct(private DependencyCallResolver $calls, private DependencyPath $paths, array $readers = [])
    {
        $this->expressions = new DependencyPathExpression($calls);
        $this->readers = self::READERS;
        foreach ($readers as $name => $reader) {
            $this->readers[strtolower(ltrim($name, '\\'))] = $reader;
        }
    }

    /**
     * Returns file dependencies only when the argument is one definite absolute path.
     *
     * @return array<string, string>
     */
    public function resolve(Node $node, Scope $scope): array
    {
        if ($node instanceof Node\Expr\Include_) {
            return $this->target($node->expr, $scope, 'include/require');
        }
        if (!$node instanceof Node\Expr\CallLike || $node->isFirstClassCallable()) {
            return [];
        }
        $call = $this->calls->resolve($node, $scope);
        if ($call === null) {
            return [];
        }
        $name = $this->calls->name($call);
        $reader = $this->readers[strtolower($name)] ?? null;
        if ($reader === null) {
            return [];
        }
        $expression = $this->calls->argument($node, $reader['position'], $reader['name']);

        return $expression !== null ? $this->target($expression, $scope, $name . '()') : [];
    }

    /**
     * Resolves constant-folded paths, including __DIR__, dirname(), and concatenation.
     *
     * @return array<string, string>
     */
    public function target(Node\Expr $expression, Scope $scope, string $description): array
    {
        $value = $this->expressions->resolve($expression, $scope);
        $path = $value !== null ? $this->paths->localFile($value) : null;

        return $path !== null ? [$path => $description] : [];
    }
}
