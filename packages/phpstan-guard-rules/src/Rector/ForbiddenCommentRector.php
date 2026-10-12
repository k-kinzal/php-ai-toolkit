<?php

declare(strict_types=1);

namespace Toolkit\Rector;

use Override;
use PhpParser\Comment;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use Rector\Rector\AbstractRector;
use Toolkit\PhpStan\Rule\Shared\ForbiddenCommentPattern;

/**
 * Removes suppression comments and standalone PHPDoc suppression lines.
 */
final class ForbiddenCommentRector extends AbstractRector
{
    /**
     * @return list<class-string<Node>>
     */
    #[Override]
    public function getNodeTypes(): array
    {
        return [Node::class];
    }

    /**
     * Preserves unrelated annotations rather than deleting a whole PHPDoc block.
     */
    public function refactor(Node $node): ?Node
    {
        $pattern = new ForbiddenCommentPattern();
        $comments = [];
        $changed = false;
        foreach ($node->getComments() as $comment) {
            $replacement = $this->withoutSuppression($comment, $pattern);
            $changed = $replacement !== $comment || $changed;
            if ($replacement !== null) {
                $comments[] = $replacement;
            }
        }
        if (!$changed) {
            return null;
        }
        $node->setAttribute('comments', $comments);

        return $node;
    }

    /**
     * Leaves inline prose mentions and ambiguous PHPDoc lines for manual review.
     */
    public function withoutSuppression(Comment $comment, ForbiddenCommentPattern $pattern): ?Comment
    {
        if (!$pattern->isHandled($comment->getText())) {
            return $comment;
        }
        if (!$comment instanceof Doc) {
            return null;
        }
        $body = substr($comment->getText(), 3, -2);
        $cleaned = preg_replace('/^[\t *]*@(?:phpstan-ignore(?:-line|-next-line)?|infection-ignore-all)\b(?![^\r\n]*@[a-zA-Z])[^\r\n]*(?:\R|$)/m', '', $body);
        if ($cleaned === null || $cleaned === $body) {
            return $comment;
        }

        return trim($cleaned, " \t\r\n*") === '' ? null : new Doc('/**' . $cleaned . '*/');
    }
}
