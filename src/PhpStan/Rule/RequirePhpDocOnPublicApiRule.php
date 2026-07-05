<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * Requires PHPDoc comments on all public API elements.
 *
 * Checks classes, interfaces, traits, enums, their public methods
 * (including magic methods), public properties, and public constants.
 * Classes in restricted test namespaces are excluded entirely.
 *
 * @implements Rule<\PhpParser\Node\Stmt\ClassLike>
 */
final class RequirePhpDocOnPublicApiRule implements Rule
{
    /** @readonly */
    private AnonymousClassDetector $anonymousClassDetector;

    /** @readonly */
    private RestrictedTestNamespaceMatcher $restrictedTestNamespaceMatcher;

    /** @readonly */
    private ClassLikeKindLabel $kindLabel;

    /** @readonly */
    private PublicApiPhpDocErrorCollector $errorCollector;

    /**
     * @param list<string> $restrictedTestNamespacePrefixes namespace prefixes to exclude from checks
     */
    public function __construct(
        array $restrictedTestNamespacePrefixes = ['Tests\\Unit', 'Tests\\Integration'],
        ?AnonymousClassDetector $anonymousClassDetector = null,
        ?RestrictedTestNamespaceMatcher $restrictedTestNamespaceMatcher = null,
        ?ClassLikeKindLabel $kindLabel = null,
        ?PublicApiPhpDocErrorCollector $errorCollector = null,
    ) {
        $this->anonymousClassDetector = $anonymousClassDetector ?? new AnonymousClassDetector();
        $this->restrictedTestNamespaceMatcher = $restrictedTestNamespaceMatcher ?? new RestrictedTestNamespaceMatcher($restrictedTestNamespacePrefixes);
        $this->kindLabel = $kindLabel ?? new ClassLikeKindLabel();
        $this->errorCollector = $errorCollector ?? new PublicApiPhpDocErrorCollector();
    }

    /**
     * @return class-string<\PhpParser\Node\Stmt\ClassLike>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\ClassLike::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassLike $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if ($this->anonymousClassDetector->isAnonymous($node, $scope)) {
            return [];
        }

        if ($this->restrictedTestNamespaceMatcher->matches($node)) {
            return [];
        }

        $className = $node->name !== null ? $node->name->toString() : '';
        $kindLabel = $this->kindLabel->label($node);

        return $this->errorCollector->errors($node, $kindLabel, $className);
    }
}
