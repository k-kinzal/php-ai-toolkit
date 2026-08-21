<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\PhpDoc\PublicApiExampleErrorCollector;
use PhpAiToolkit\PhpStan\Rule\Shared\AnonymousClassDetector;
use PhpAiToolkit\PhpStan\Rule\Shared\ClassLikeKindLabel;
use PhpAiToolkit\PhpStan\Rule\Shared\RestrictedTestNamespaceMatcher;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * Requires a runnable example on every declaration marked public API.
 *
 * A declaration states that it is public API by carrying "@visibility public".
 * Prose describes what a symbol is for; an example states what calling it does,
 * and because doctest runs it, the statement cannot go stale without the build
 * saying so. Classes in restricted test namespaces are excluded entirely.
 *
 * @implements Rule<\PhpParser\Node\Stmt\ClassLike>
 */
final class RequireExampleOnPublicApiRule implements Rule
{
    /** @readonly */
    private AnonymousClassDetector $anonymousClassDetector;

    /** @readonly */
    private RestrictedTestNamespaceMatcher $restrictedTestNamespaceMatcher;

    /** @readonly */
    private ClassLikeKindLabel $kindLabel;

    /** @readonly */
    private PublicApiExampleErrorCollector $errorCollector;

    /**
     * @param list<string> $restrictedTestNamespacePrefixes namespace prefixes to exclude from checks
     */
    public function __construct(
        array $restrictedTestNamespacePrefixes = ['Tests\\Unit', 'Tests\\Integration'],
        ?AnonymousClassDetector $anonymousClassDetector = null,
        ?RestrictedTestNamespaceMatcher $restrictedTestNamespaceMatcher = null,
        ?ClassLikeKindLabel $kindLabel = null,
        ?PublicApiExampleErrorCollector $errorCollector = null,
    ) {
        $this->anonymousClassDetector = $anonymousClassDetector ?? new AnonymousClassDetector();
        $this->restrictedTestNamespaceMatcher = $restrictedTestNamespaceMatcher ?? new RestrictedTestNamespaceMatcher($restrictedTestNamespacePrefixes);
        $this->kindLabel = $kindLabel ?? new ClassLikeKindLabel();
        $this->errorCollector = $errorCollector ?? new PublicApiExampleErrorCollector();
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

        return $this->errorCollector->errors($node, $this->kindLabel->label($node), $className);
    }
}
