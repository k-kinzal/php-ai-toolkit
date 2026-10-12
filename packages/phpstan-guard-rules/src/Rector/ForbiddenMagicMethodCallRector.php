<?php

declare(strict_types=1);

namespace Toolkit\Rector;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\Cast\String_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use Rector\Rector\AbstractRector;

/**
 * Replaces direct string and invocation protocol calls with language syntax.
 */
final class ForbiddenMagicMethodCallRector extends AbstractRector
{
    /**
     * @return list<class-string<Node>>
     */
    #[Override]
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * Rewrites the supported protocols; other magic calls need design decisions.
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof MethodCall) {
            return null;
        }

        if (!$node->name instanceof Identifier || $node->isFirstClassCallable()) {
            return null;
        }

        $name = $node->name->toLowerString();
        if ($name === '__tostring' && $node->args === []) {
            return new String_($node->var);
        }
        if ($name === '__invoke') {
            return new FuncCall($node->var, $node->args);
        }

        return null;
    }
}
