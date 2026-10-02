<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Visibility;

use function array_shift;
use function sprintf;
use function strtolower;

/**
 * Indexes declarations gathered from PHPStan's analysed files.
 */
final class VisibilityDeclarationIndex
{
    /** @var array<string, array{className: string, memberName: ?string, symbol: string, kind: string, namespace: string, docComment: ?string, line: int, file: string}> */
    private array $classes = [];

    /** @var array<string, array{className: string, memberName: ?string, symbol: string, kind: string, namespace: string, docComment: ?string, line: int, file: string}> */
    private array $members = [];

    /** @var array<string, list<string>> */
    private array $parents = [];

    /** @var list<array{className: string, memberName: ?string, symbol: string, kind: string, namespace: string, docComment: ?string, line: int, file: string}> */
    private array $declarations = [];

    /**
     * Adds one class-like collector result to the index.
     *
     * @param array{
     *     class: array{className: string, memberName: null, symbol: string, kind: string, namespace: string, docComment: ?string, line: int},
     *     parents: list<string>,
     *     members: list<array{className: string, memberName: string, symbol: string, kind: string, namespace: string, docComment: ?string, line: int}>
     * } $collected
     */
    public function add(string $file, array $collected): void
    {
        $class = $collected['class'] + ['file' => $file];
        $classKey = strtolower($class['className']);
        $this->classes[$classKey] = $class;
        $this->parents[$classKey] = $collected['parents'];
        $this->declarations[] = $class;

        foreach ($collected['members'] as $member) {
            $declaration = $member + ['file' => $file];
            $this->members[$this->memberKey($member['className'], $member['memberName'])] = $declaration;
            $this->declarations[] = $declaration;
        }
    }

    /**
     * Returns a class-like declaration from the analysed files.
     *
     * @return array{className: string, memberName: ?string, symbol: string, kind: string, namespace: string, docComment: ?string, line: int, file: string}|null
     */
    public function classDeclaration(string $className): ?array
    {
        return $this->classes[strtolower($className)] ?? null;
    }

    /**
     * Returns a member declaration, following analysed parents and traits.
     *
     * @return array{className: string, memberName: ?string, symbol: string, kind: string, namespace: string, docComment: ?string, line: int, file: string}|null
     */
    public function memberDeclaration(string $className, string $memberName): ?array
    {
        $seen = [];
        $queue = [$className];

        while ($queue !== []) {
            $current = array_shift($queue);
            $key = strtolower($current);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $declaration = $this->members[$this->memberKey($current, $memberName)] ?? null;
            if ($declaration !== null) {
                return $declaration;
            }

            foreach ($this->parents[$key] ?? [] as $parent) {
                $queue[] = $parent;
            }
        }

        return null;
    }

    /**
     * Returns every declaration in the index.
     *
     * @return list<array{className: string, memberName: ?string, symbol: string, kind: string, namespace: string, docComment: ?string, line: int, file: string}>
     */
    public function declarations(): array
    {
        return $this->declarations;
    }

    /**
     * Returns the case-sensitive member lookup key used by the standalone implementation.
     */
    public function memberKey(string $className, string $memberName): string
    {
        return sprintf('%s::%s', strtolower($className), $memberName);
    }
}
