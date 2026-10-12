<?php

declare(strict_types=1);

namespace Toolkit\Rector;

use Override;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Catch_;
use Rector\Rector\AbstractRector;
use Toolkit\PhpStan\Rule\ExceptionHandling\CatchThrowCollector;
use Toolkit\PhpStan\Rule\ExceptionHandling\ThrowChainEvaluator;
use Toolkit\PhpStan\Rule\Shared\ThrownExpression;

/**
 * Chains caught exceptions into known built-in exception constructors.
 */
final class RequireExceptionChainingRector extends AbstractRector
{
    /**
     * @return list<class-string<Node>>
     */
    #[Override]
    public function getNodeTypes(): array
    {
        return [Catch_::class];
    }

    /**
     * Adds previous only when its argument is absent and its signature is known.
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof Catch_) {
            return null;
        }

        if (!$node->var instanceof Variable || !is_string($node->var->name)) {
            return null;
        }

        $changed = false;
        foreach ((new CatchThrowCollector())->collect($node->stmts) as $throw) {
            $expression = (new ThrownExpression())->of($throw);
            if (!$expression instanceof New_ || !$this->acceptsPrevious($expression)) {
                continue;
            }
            if (!(new ThrowChainEvaluator())->violates($throw, $node->var->name)) {
                continue;
            }
            $expression->args[] = new Arg(clone $node->var, false, false, [], new Identifier('previous'));
            $changed = true;
        }

        return $changed ? $node : null;
    }

    /**
     * Limits changes to built-ins with the standard message/code/previous signature.
     */
    public function acceptsPrevious(New_ $new): bool
    {
        if (!$new->class instanceof Name || !in_array(strtolower($this->getName($new->class)), [
            'exception', 'logicexception', 'badfunctioncallexception',
            'badmethodcallexception', 'domainexception', 'invalidargumentexception',
            'lengthexception', 'outofrangeexception', 'runtimeexception',
            'outofboundsexception', 'overflowexception', 'rangeexception',
            'underflowexception', 'unexpectedvalueexception',
        ], true)) {
            return false;
        }
        if (count($new->args) > 2) {
            return false;
        }
        foreach ($new->args as $arg) {
            if (!$arg instanceof Arg || $arg->unpack || ($arg->name !== null && !in_array($arg->name->toString(), ['message', 'code'], true))) {
                return false;
            }
        }

        return true;
    }
}
