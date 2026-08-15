<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\RequireThrowsTagOnDirectThrow;

use function array_pop;
use function array_values;
use function count;
use function is_string;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Finally_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Collects throw statements that escape the traversed statement list.
 *
 * Tracks enclosing try/catch nesting so that a throw inside a try block
 * records the catch types guarding it, while throws inside catch and finally
 * blocks are not protected by their own try. Nested closures, arrow
 * functions, and anonymous classes are skipped because their throws do not
 * escape the enclosing method at declaration time.
 */
final class ThrowSiteVisitor extends NodeVisitorAbstract
{
    /** @var list<array{types: list<Name>, active: bool}> */
    private array $tryFrames = [];

    /** @var list<array{var: ?string, types: list<Name>}> */
    private array $catchBindings = [];

    /** @var list<ThrowSite> */
    private array $sites = [];

    /**
     * Tracks try/catch nesting and records throw statements.
     *
     * @return ?int a traversal instruction, or null to continue
     */
    #[Override]
    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Closure || $node instanceof ArrowFunction || $node instanceof Class_) {
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof TryCatch) {
            $types = [];
            foreach ($node->catches as $catch) {
                foreach ($catch->types as $type) {
                    $types[] = $type;
                }
            }
            $this->tryFrames[] = ['types' => $types, 'active' => true];

            return null;
        }

        if ($node instanceof Catch_ || $node instanceof Finally_) {
            $frameIndex = count($this->tryFrames) - 1;
            if ($frameIndex >= 0) {
                $this->tryFrames[$frameIndex]['active'] = false;
            }
            if ($node instanceof Catch_) {
                $variable = $node->var;
                $variableName = $variable instanceof Variable && is_string($variable->name) ? $variable->name : null;
                $this->catchBindings[] = ['var' => $variableName, 'types' => array_values($node->types)];
            }

            return null;
        }

        if ($node instanceof Throw_) {
            $this->recordThrow($node);
        }

        return null;
    }

    /**
     * Restores try/catch tracking when leaving try, catch, and finally nodes.
     *
     * @return null always null to keep the node unchanged
     */
    #[Override]
    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof TryCatch) {
            array_pop($this->tryFrames);

            return null;
        }

        if ($node instanceof Catch_ || $node instanceof Finally_) {
            $frameIndex = count($this->tryFrames) - 1;
            if ($frameIndex >= 0) {
                $this->tryFrames[$frameIndex]['active'] = true;
            }
            if ($node instanceof Catch_) {
                array_pop($this->catchBindings);
            }
        }

        return null;
    }

    /**
     * Records one throw statement with its thrown names and active guards.
     */
    public function recordThrow(Throw_ $node): void
    {
        $thrownNames = [];
        $expr = $node->expr;
        if ($expr instanceof New_ && $expr->class instanceof Name) {
            $thrownNames = [$expr->class];
        } elseif ($expr instanceof Variable && is_string($expr->name)) {
            for ($index = count($this->catchBindings) - 1; $index >= 0; $index--) {
                if ($this->catchBindings[$index]['var'] === $expr->name) {
                    $thrownNames = $this->catchBindings[$index]['types'];
                    break;
                }
            }
        }

        if ($thrownNames === []) {
            return;
        }

        $guardNames = [];
        foreach ($this->tryFrames as $frame) {
            if (!$frame['active']) {
                continue;
            }
            foreach ($frame['types'] as $type) {
                $guardNames[] = $type;
            }
        }

        $this->sites[] = new ThrowSite($thrownNames, $guardNames, $node->getStartLine());
    }

    /**
     * Returns the throw sites collected during traversal.
     *
     * @return list<ThrowSite>
     */
    public function sites(): array
    {
        return $this->sites;
    }
}
