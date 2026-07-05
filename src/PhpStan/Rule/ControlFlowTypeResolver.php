<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\While_;

/**
 * Resolves prohibited control-flow statement labels.
 */
final class ControlFlowTypeResolver
{
    /**
     * Returns the control-flow label for a prohibited node.
     */
    public function type(\PhpParser\Node $node): ?string
    {
        if ($node instanceof If_) {
            return 'if';
        }
        if ($node instanceof For_) {
            return 'for';
        }
        if ($node instanceof Foreach_) {
            return 'foreach';
        }
        if ($node instanceof While_) {
            return 'while';
        }
        if ($node instanceof Do_) {
            return 'do-while';
        }
        if ($node instanceof Switch_) {
            return 'switch';
        }
        if ($node instanceof Match_) {
            return 'match';
        }

        return null;
    }
}
