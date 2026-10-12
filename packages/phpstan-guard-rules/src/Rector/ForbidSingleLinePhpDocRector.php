<?php

declare(strict_types=1);

namespace Toolkit\Rector;

use Override;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Toolkit\PhpStan\Rule\Shared\AnonymousClassDetector;

/**
 * Expands public PHPDoc blocks without changing their contents.
 */
final class ForbidSingleLinePhpDocRector extends AbstractRector
{
    /**
     * @return list<class-string<Node>>
     */
    #[Override]
    public function getNodeTypes(): array
    {
        return [ClassLike::class];
    }

    /**
     * Expands comments on named declarations and their public members.
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof ClassLike) {
            return null;
        }

        if ((new AnonymousClassDetector())->isAnonymous($node, ScopeFetcher::fetch($node))) {
            return null;
        }

        $changed = $this->expand($node);
        foreach ($node->stmts as $member) {
            if (($member instanceof ClassMethod || $member instanceof Property || $member instanceof ClassConst) && $member->isPublic()) {
                $changed = $this->expand($member) || $changed;
            }
        }

        return $changed ? $node : null;
    }

    /**
     * Replaces one single-line comment while preserving its annotation text.
     */
    public function expand(Node $node): bool
    {
        $doc = $node->getDocComment();
        if ($doc === null || str_contains($doc->getText(), "\n") || str_contains($doc->getText(), "\r")) {
            return false;
        }

        $node->setDocComment(new Doc("/**\n * " . trim(substr($doc->getText(), 3, -2)) . "\n */"));

        return true;
    }
}
