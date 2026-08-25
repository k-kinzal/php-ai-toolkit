<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\ControlFlow;

use function in_array;

use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

use function usort;

/**
 * Requires a dispatch on a class name to name a branch for every class it can be.
 *
 * This is the half of RequireExhaustiveDispatchRule that a sealed hierarchy needs. Where a
 * subject's type carries its own values — an enum, a bool, a union of literals — that rule
 * reads them straight out of it. An interface or an abstract class carries no such list,
 * because PHP has no `sealed`, so the classes below it are gathered from the analysed code
 * and the answer arrives once the whole tree has been read.
 *
 * Only `match ($shape::class)` and `switch (get_class($shape))` are read this way. Those
 * two say which object the table is answering for, and their branches can hold nothing but
 * class names. `match (true)` says neither: any condition at all may sit in an arm, so
 * there is no set of classes such a table can be held to.
 *
 * @implements Rule<CollectedDataNode>
 */
final class RequireExhaustiveClassDispatchRule implements Rule
{
    /** @readonly */
    private RequireExhaustiveDispatchErrorBuilder $errorBuilder;

    /**
     * Creates the rule from the builder that words what a dispatch left out.
     */
    public function __construct(?RequireExhaustiveDispatchErrorBuilder $errorBuilder = null)
    {
        $this->errorBuilder = $errorBuilder ?? new RequireExhaustiveDispatchErrorBuilder();
    }

    /**
     * @return class-string<CollectedDataNode>
     */
    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /**
     * @param CollectedDataNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        unset($scope);
        $classes = [];
        foreach ($node->get(ClassAncestorCollector::class) as $collected) {
            foreach ($collected as $class) {
                $classes[] = $class;
            }
        }

        $index = new SubtypeIndex($classes);
        $dispatches = [];
        foreach ($node->get(ClassNameDispatchCollector::class) as $file => $collected) {
            foreach ($collected as $dispatch) {
                $dispatches[] = ['file' => $file, 'dispatch' => $dispatch];
            }
        }

        usort($dispatches, static fn (array $left, array $right): int => [$left['file'], $left['dispatch']['line']] <=> [$right['file'], $right['dispatch']['line']]);

        $errors = [];
        foreach ($dispatches as $entry) {
            $error = $this->error($entry['file'], $entry['dispatch'], $index);
            if ($error === null) {
                continue;
            }

            $errors[] = $error;
        }

        return $errors;
    }

    /**
     * Returns the error of one dispatch, or null when it names every class it can be.
     *
     * A dispatch that names none of the classes below the subject is not a table over that
     * hierarchy at all, so it is left alone the same way a comparison on a closed value is.
     *
     * @param array{roots: list<string>, named: list<string>, catchAll: bool, line: int, construct: string} $dispatch
     */
    public function error(string $file, array $dispatch, SubtypeIndex $index): ?IdentifierRuleError
    {
        $classes = $index->instantiableUnder($dispatch['roots']);
        $unhandled = [];
        $handled = 0;
        foreach ($classes as $class) {
            if (in_array($class, $dispatch['named'], true)) {
                ++$handled;

                continue;
            }

            $unhandled[] = $class;
        }

        if ($handled === 0 || $unhandled === []) {
            return null;
        }

        if ($dispatch['construct'] === ClassNameDispatchCollector::MATCH_CONSTRUCT) {
            return $this->errorBuilder->buildMatchCatchAll($unhandled, $file, $dispatch['line']);
        }

        if ($dispatch['catchAll']) {
            return $this->errorBuilder->buildSwitchCatchAll($unhandled, $file, $dispatch['line']);
        }

        return $this->errorBuilder->buildSwitchUnhandled($unhandled, $file, $dispatch['line']);
    }
}
