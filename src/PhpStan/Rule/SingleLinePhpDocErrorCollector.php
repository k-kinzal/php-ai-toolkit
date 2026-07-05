<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;

/**
 * Collects single-line PHPDoc errors from public class-like API elements.
 */
final class SingleLinePhpDocErrorCollector
{
    /** @readonly */
    private AnonymousClassDetector $anonymousClassDetector;

    /** @readonly */
    private SingleLinePhpDocDetector $singleLinePhpDocDetector;

    /** @readonly */
    private SingleLinePhpDocErrorBuilder $errorBuilder;

    /**
     * Creates a collector from anonymous-class detection and PHPDoc error building.
     */
    public function __construct(
        ?AnonymousClassDetector $anonymousClassDetector = null,
        ?SingleLinePhpDocDetector $singleLinePhpDocDetector = null,
        ?SingleLinePhpDocErrorBuilder $errorBuilder = null,
    ) {
        $this->anonymousClassDetector = $anonymousClassDetector ?? new AnonymousClassDetector();
        $this->singleLinePhpDocDetector = $singleLinePhpDocDetector ?? new SingleLinePhpDocDetector();
        $this->errorBuilder = $errorBuilder ?? new SingleLinePhpDocErrorBuilder();
    }

    /**
     * Returns errors for single-line PHPDoc comments on public API elements.
     *
     * @return list<IdentifierRuleError>
     */
    public function errors(\PhpParser\Node\Stmt\ClassLike $node, Scope $scope): array
    {
        if ($this->anonymousClassDetector->isAnonymous($node, $scope)) {
            return [];
        }

        $errors = [];
        $classDoc = $node->getDocComment();
        if ($classDoc !== null && $this->singleLinePhpDocDetector->isSingleLine($classDoc->getText())) {
            $errors[] = $this->errorBuilder->error($classDoc->getText(), $classDoc->getStartLine());
        }

        foreach ($node->getMethods() as $method) {
            $doc = $method->getDocComment();
            if ($method->isPublic() && $doc !== null && $this->singleLinePhpDocDetector->isSingleLine($doc->getText())) {
                $errors[] = $this->errorBuilder->error($doc->getText(), $doc->getStartLine());
            }
        }

        foreach ($node->getProperties() as $property) {
            $doc = $property->getDocComment();
            if ($property->isPublic() && $doc !== null && $this->singleLinePhpDocDetector->isSingleLine($doc->getText())) {
                $errors[] = $this->errorBuilder->error($doc->getText(), $doc->getStartLine());
            }
        }

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof \PhpParser\Node\Stmt\ClassConst || !$stmt->isPublic()) {
                continue;
            }

            $doc = $stmt->getDocComment();
            if ($doc !== null && $this->singleLinePhpDocDetector->isSingleLine($doc->getText())) {
                $errors[] = $this->errorBuilder->error($doc->getText(), $doc->getStartLine());
            }
        }

        return $errors;
    }
}
