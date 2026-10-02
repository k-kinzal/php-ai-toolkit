<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Mutation;

use function array_keys;
use function in_array;
use function sprintf;
use function str_starts_with;
use function substr;

/**
 * Resolves mutation effects across aliases, calls, and inherited contracts.
 *
 * @phpstan-type Declaration array{key: string, symbol: string, line: int, file: string, parameters: list<array{name: string, variadic: bool}>, mutable: list<string>, this: bool, global: bool, problems: list<string>, prototypes: list<string>, static: bool, constructor?: bool}
 * @phpstan-type AliasOperation array{kind: 'alias', caller: string, line: int, file: string, variable: string, root: string}
 * @phpstan-type DirectOperation array{kind: 'mutation', caller: string, line: int, file: string, root: string}
 * @phpstan-type CallOperation array{kind: 'call', caller: string, line: int, file: string, callees: list<string>, receiver: string, arguments: list<array{index: int, name: ?string, root: string}>}
 * @phpstan-type Operation AliasOperation|DirectOperation|CallOperation
 * @phpstan-type Violation array{file: string, line: int, identifier: string, symbol: string, message: string}
 * @phpstan-type EffectSite array{line: int, file: string}
 */
final class MutationInspector
{
    /**
     * @param list<Declaration> $declarations
     * @param list<Operation> $operations
     * @return list<Violation>
     */
    public function violations(array $declarations, array $operations): array
    {
        $index = [];
        foreach ($declarations as $declaration) {
            $index[$declaration['key']] = $declaration;
        }

        $permissionCache = [];
        foreach (array_keys($index) as $key) {
            $this->permissions($key, $index, $permissionCache, []);
        }

        $violations = $this->contractProblems($declarations);
        $violations = [...$violations, ...$this->overrideProblems($declarations, $index, $permissionCache)];
        $actual = $this->actualEffects($index, $operations, $permissionCache);
        foreach ($index as $key => $declaration) {
            $allowed = $permissionCache[$key] ?? [];
            foreach ($actual[$key] ?? [] as $effect => $site) {
                if (isset($allowed[$effect])) {
                    continue;
                }

                $violations[] = $this->undeclared($declaration, $effect, $site);
            }
        }

        return $violations;
    }

    /**
     * @param list<Declaration> $declarations
     * @return list<Violation>
     */
    public function contractProblems(array $declarations): array
    {
        $violations = [];
        foreach ($declarations as $declaration) {
            foreach ($declaration['problems'] as $problem) {
                $violations[] = [
                    'file' => $declaration['file'],
                    'line' => $declaration['line'],
                    'identifier' => 'customRules.mutationInvalidContract',
                    'symbol' => $declaration['symbol'],
                    'message' => sprintf('Invalid mutation contract on %s: %s', $declaration['symbol'], $problem),
                ];
            }
        }

        return $violations;
    }

    /**
     * @param list<Declaration> $declarations
     * @param array<string, Declaration> $index
     * @param array<string, array<string, true>> $permissions
     * @return list<Violation>
     */
    public function overrideProblems(array $declarations, array $index, array $permissions): array
    {
        $violations = [];
        foreach ($declarations as $declaration) {
            $own = $this->ownPermissions($declaration);
            foreach ($declaration['prototypes'] as $prototypeKey) {
                if (!isset($index[$prototypeKey])) {
                    continue;
                }

                foreach (array_keys($own) as $effect) {
                    if (isset($permissions[$prototypeKey][$effect])) {
                        continue;
                    }

                    $violations[] = [
                        'file' => $declaration['file'],
                        'line' => $declaration['line'],
                        'identifier' => 'customRules.mutationOverrideWidened',
                        'symbol' => $declaration['symbol'],
                        'message' => sprintf(
                            'Mutation contract on %s widens inherited effect %s. Remove that effect, or declare it on %s so every caller sees the same permission.',
                            $declaration['symbol'],
                            $this->effectLabel($declaration, $effect),
                            $index[$prototypeKey]['symbol'],
                        ),
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * @param array<string, Declaration> $index
     * @param list<Operation> $operations
     * @param array<string, array<string, true>> $permissions
     * @return array<string, array<string, EffectSite>>
     */
    public function actualEffects(array $index, array $operations, array $permissions): array
    {
        $actual = [];
        $aliases = [];
        $calls = [];
        foreach ($operations as $operation) {
            if (!isset($index[$operation['caller']])) {
                continue;
            }

            if ($operation['kind'] === 'alias') {
                $aliases[$operation['caller']][$operation['variable']][] = $operation['root'];
            } elseif ($operation['kind'] === 'call') {
                $calls[] = $operation;
            } elseif ($operation['kind'] === 'mutation') {
                foreach ($this->rootEffects($operation['root'], $index[$operation['caller']], $aliases[$operation['caller']] ?? [], []) as $effect) {
                    $actual[$operation['caller']][$effect] = ['line' => $operation['line'], 'file' => $operation['file']];
                }
            }
        }

        do {
            $changed = false;
            foreach ($calls as $call) {
                foreach ($call['callees'] as $calleeKey) {
                    if (!isset($index[$calleeKey])) {
                        continue;
                    }

                    $calleeEffects = ($actual[$calleeKey] ?? []) + $this->permissionSites($permissions[$calleeKey] ?? [], $call);
                    foreach (array_keys($calleeEffects) as $effect) {
                        foreach ($this->mappedCallEffects($effect, $call, $index[$calleeKey], $index[$call['caller']], $aliases[$call['caller']] ?? []) as $mapped) {
                            if (!isset($actual[$call['caller']][$mapped])) {
                                $actual[$call['caller']][$mapped] = ['line' => $call['line'], 'file' => $call['file']];
                                $changed = true;
                            }
                        }
                    }
                }
            }
        } while ($changed);

        return $actual;
    }

    /**
     * @param array<string, true> $permissions
     * @param CallOperation $call
     * @return array<string, EffectSite>
     */
    public function permissionSites(array $permissions, array $call): array
    {
        $sites = [];
        foreach (array_keys($permissions) as $effect) {
            $sites[$effect] = ['line' => $call['line'], 'file' => $call['file']];
        }

        return $sites;
    }

    /**
     * @param CallOperation $call
     * @param Declaration $callee
     * @param Declaration $caller
     * @param array<string, list<string>> $aliases
     * @return list<string>
     */
    public function mappedCallEffects(string $effect, array $call, array $callee, array $caller, array $aliases): array
    {
        if ($effect === 'global') {
            return ['global'];
        }

        if ($effect === 'this') {
            return $this->rootEffects($call['receiver'], $caller, $aliases, []);
        }

        $position = (int) substr($effect, 4);
        $parameter = $callee['parameters'][$position] ?? null;
        if ($parameter === null) {
            return [];
        }

        $effects = [];
        foreach ($call['arguments'] as $argument) {
            $matches = $argument['name'] === $parameter['name']
                || ($argument['name'] === null && ($argument['index'] === $position || ($parameter['variadic'] && $argument['index'] >= $position)));
            if ($matches) {
                $effects = [...$effects, ...$this->rootEffects($argument['root'], $caller, $aliases, [])];
            }
        }

        return array_values(array_unique($effects));
    }

    /**
     * @param Declaration $declaration
     * @param array<string, list<string>> $aliases
     * @param list<string> $seen
     * @return list<string>
     */
    public function rootEffects(string $root, array $declaration, array $aliases, array $seen): array
    {
        if ($root === 'this' && ($declaration['constructor'] ?? false)) {
            return [];
        }

        if ($root === 'this' || $root === 'global') {
            return [$root];
        }

        if (!str_starts_with($root, 'var:')) {
            return [];
        }

        $variable = substr($root, 4);
        foreach ($declaration['parameters'] as $position => $parameter) {
            if ($parameter['name'] === $variable) {
                return ['arg:' . $position];
            }
        }

        if (in_array($variable, $seen, true)) {
            return [];
        }

        $effects = [];
        foreach ($aliases[$variable] ?? [] as $aliasRoot) {
            $effects = [...$effects, ...$this->rootEffects($aliasRoot, $declaration, $aliases, [...$seen, $variable])];
        }

        return array_values(array_unique($effects));
    }

    /**
     * @param Declaration $declaration
     * @return array<string, true>
     */
    public function ownPermissions(array $declaration): array
    {
        $permissions = [];
        foreach ($declaration['parameters'] as $position => $parameter) {
            if (in_array($parameter['name'], $declaration['mutable'], true)) {
                $permissions['arg:' . $position] = true;
            }
        }

        if ($declaration['this']) {
            $permissions['this'] = true;
        }

        if ($declaration['global']) {
            $permissions['global'] = true;
        }

        return $permissions;
    }

    /**
     * @param array<string, Declaration> $index
     * @param array<string, array<string, true>> $cache
     * @param list<string> $visiting
     * @return array<string, true>
     */
    public function permissions(string $key, array $index, array &$cache, array $visiting): array
    {
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        if (!isset($index[$key]) || in_array($key, $visiting, true)) {
            return [];
        }

        $permissions = $this->ownPermissions($index[$key]);
        foreach ($index[$key]['prototypes'] as $prototypeKey) {
            $permissions += $this->permissions($prototypeKey, $index, $cache, [...$visiting, $key]);
        }

        return $cache[$key] = $permissions;
    }

    /**
     * @param Declaration $declaration
     * @param EffectSite $site
     * @return Violation
     */
    public function undeclared(array $declaration, string $effect, array $site): array
    {
        $label = $this->effectLabel($declaration, $effect);
        $fix = $effect === 'this'
            ? 'Add "@mutation $this" to the method PHPDoc.'
            : ($effect === 'global'
                ? 'Add "@mutation global" to the callable PHPDoc.'
                : sprintf('Add +mut immediately after %s in its @param tag.', $label));

        return [
            'file' => $site['file'],
            'line' => $site['line'],
            'identifier' => 'customRules.mutationUndeclared',
            'symbol' => $declaration['symbol'],
            'message' => sprintf('%s mutates %s without declaring that effect. %s', $declaration['symbol'], $label, $fix),
        ];
    }

    /**
     * @param Declaration $declaration
     */
    public function effectLabel(array $declaration, string $effect): string
    {
        if ($effect === 'this') {
            return '$this';
        }

        if ($effect === 'global') {
            return 'global state';
        }

        $parameter = $declaration['parameters'][(int) substr($effect, 4)] ?? null;

        return '$' . ($parameter['name'] ?? '?');
    }
}
