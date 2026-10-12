<?php

declare(strict_types=1);

namespace Toolkit\Rector;

use Override;
use PhpParser\Comment;
use PhpParser\Node;
use Rector\Rector\AbstractRector;
use Toolkit\PhpStan\Rule\Shared\ForbiddenCommentPattern;
use Toolkit\PhpStan\Support\NonDocCommentContext;

/**
 * Removes prohibited ordinary comments while preserving allowed catch/array notes.
 */
final class ForbidNonDocCommentRector extends AbstractRector
{
    private string $source = '';

    /**
     * @var array<int, true>
     */
    private array $offsets = [];

    /**
     * @return list<class-string<Node>>
     */
    #[Override]
    public function getNodeTypes(): array
    {
        return [Node::class];
    }

    /**
     * Uses token offsets so allowed and forbidden comments can share a line.
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->getComments() === []) {
            return null;
        }
        $source = $this->getFile()->getFileContent();
        if ($source !== $this->source) {
            $this->source = $source;
            $this->offsets = $this->forbiddenOffsets($source);
        }
        $comments = array_values(array_filter($node->getComments(), fn (Comment $comment): bool => !isset($this->offsets[$comment->getStartFilePos()])));
        if ($comments === $node->getComments()) {
            return null;
        }
        $node->setAttribute('comments', $comments);

        return $node;
    }

    /**
     * Locates violations using the PHPStan rule's token context and exemptions.
     *
     * @return array<int, true>
     */
    public function forbiddenOffsets(string $source): array
    {
        $context = new NonDocCommentContext();
        $pattern = new ForbiddenCommentPattern();
        $offsets = [];
        $offset = 0;
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $context->registerStringToken($token);
                $offset += strlen($token);
                continue;
            }
            [$type, $text] = $token;
            if ($type !== T_COMMENT) {
                $context->registerToken($type, $text);
            } elseif (!$pattern->isHandled($text) && !(str_starts_with($text, '//') && $context->allowsLineComment())) {
                $offsets[$offset] = true;
            }
            $offset += strlen($text);
        }

        return $offsets;
    }
}
