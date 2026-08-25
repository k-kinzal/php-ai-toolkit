<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ControlFlow;

use function count;

use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Records the class-name dispatches the analysed code contains.
 *
 * `match ($shape::class)` is the one form in which PHP states what a branch table is
 * answering for. The subject names the object, so the set of classes to cover follows from
 * that object's type, and every branch has to be a class name, so no unrelated condition
 * can enter the table. `match (true) { $shape instanceof Circle => ... }` states neither:
 * its subject is a constant and its branches take any condition at all, which is why a
 * hierarchy is never read out of that form.
 *
 * A subject whose classes are all final resolves to literal class names on its own and is
 * left to the rule that reads values straight out of the type.
 *
 * @implements Collector<\PhpParser\Node, array{roots: list<string>, named: list<string>, catchAll: bool, line: int, construct: string}>
 */
final class ClassNameDispatchCollector implements Collector
{
    /**
     * Marks a dispatch written as a match expression.
     */
    public const MATCH_CONSTRUCT = 'match';

    /**
     * Marks a dispatch written as a switch statement.
     */
    public const SWITCH_CONSTRUCT = 'switch';

    /** @readonly */
    private DispatchSubjectResolver $subjectResolver;

    /**
     * Creates a collector from the resolver that reads the object out of a subject.
     */
    public function __construct(?DispatchSubjectResolver $subjectResolver = null)
    {
        $this->subjectResolver = $subjectResolver ?? new DispatchSubjectResolver();
    }

    /**
     * @return class-string<\PhpParser\Node>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node::class;
    }

    /**
     * @param \PhpParser\Node $node
     * @return array{roots: list<string>, named: list<string>, catchAll: bool, line: int, construct: string}|null
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): ?array
    {
        if ($node instanceof \PhpParser\Node\Expr\Match_) {
            $conditions = [];
            $catchAll = false;
            foreach ($node->arms as $arm) {
                if ($arm->conds === null || $arm->conds === []) {
                    $catchAll = true;

                    continue;
                }

                foreach ($arm->conds as $armCondition) {
                    $conditions[] = $armCondition;
                }
            }

            if (!$catchAll) {
                return null;
            }

            return $this->collect($node->cond, $conditions, true, self::MATCH_CONSTRUCT, $node->getStartLine(), $scope);
        }

        if (!$node instanceof \PhpParser\Node\Stmt\Switch_) {
            return null;
        }

        $conditions = [];
        $catchAll = false;
        foreach ($node->cases as $case) {
            if ($case->cond === null) {
                $catchAll = true;

                continue;
            }

            $conditions[] = $case->cond;
        }

        return $this->collect($node->cond, $conditions, $catchAll, self::SWITCH_CONSTRUCT, $node->getStartLine(), $scope);
    }

    /**
     * Returns what one dispatch claims about a class name, or null when it claims nothing.
     *
     * @param list<\PhpParser\Node\Expr> $conditions
     * @return array{roots: list<string>, named: list<string>, catchAll: bool, line: int, construct: string}|null
     */
    public function collect(\PhpParser\Node\Expr $subject, array $conditions, bool $catchAll, string $construct, int $line, Scope $scope): ?array
    {
        $object = $this->subjectResolver->namedObject($subject);
        if ($object === null || $scope->getType($subject)->getFiniteTypes() !== []) {
            return null;
        }

        $roots = $scope->getType($object)->getObjectClassNames();
        $named = $this->namedClasses($conditions, $scope);
        if ($roots === [] || $named === null || $named === []) {
            return null;
        }

        return [
            'roots' => $roots,
            'named' => $named,
            'catchAll' => $catchAll,
            'line' => $line,
            'construct' => $construct,
        ];
    }

    /**
     * Returns the class each branch names, or null when a branch names something else.
     *
     * @param list<\PhpParser\Node\Expr> $conditions
     * @return list<string>|null
     */
    public function namedClasses(array $conditions, Scope $scope): ?array
    {
        $named = [];
        foreach ($conditions as $condition) {
            $constantStrings = $scope->getType($condition)->getConstantStrings();
            if (count($constantStrings) !== 1) {
                return null;
            }

            $named[] = $constantStrings[0]->getValue();
        }

        return $named;
    }
}
