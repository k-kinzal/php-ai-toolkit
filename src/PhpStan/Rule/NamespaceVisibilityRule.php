<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use function array_merge;

use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\ExemptCallerNamespaces;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\TypeReferenceInspector;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityTagInspector;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityUsageInspector;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * Enforces the namespace visibility scopes declared with @visibility.
 *
 * PHP has no counterpart to Rust's pub(crate), pub(super), or pub(in path): public is
 * public to the whole program. The @visibility tag states which namespace subtree a
 * declaration belongs to, and this rule reports every call, instantiation, member access,
 * and type reference that reaches it from outside that subtree.
 *
 * @implements Rule<\PhpParser\Node>
 */
final class NamespaceVisibilityRule implements Rule
{
    /** @readonly */
    private ExemptCallerNamespaces $exemptCallerNamespaces;

    /** @readonly */
    private VisibilityUsageInspector $usageInspector;

    /** @readonly */
    private TypeReferenceInspector $typeReferenceInspector;

    /** @readonly */
    private VisibilityTagInspector $tagInspector;

    /**
     * @param list<string> $exemptNamespacePrefixes namespace prefixes whose code may ignore every scope
     */
    public function __construct(
        array $exemptNamespacePrefixes = [],
        ?VisibilityUsageInspector $usageInspector = null,
        ?TypeReferenceInspector $typeReferenceInspector = null,
        ?VisibilityTagInspector $tagInspector = null,
    ) {
        $this->exemptCallerNamespaces = new ExemptCallerNamespaces($exemptNamespacePrefixes);
        $this->usageInspector = $usageInspector ?? new VisibilityUsageInspector();
        $this->typeReferenceInspector = $typeReferenceInspector ?? new TypeReferenceInspector();
        $this->tagInspector = $tagInspector ?? new VisibilityTagInspector();
    }

    /**
     * @return class-string<\PhpParser\Node>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node::class;
    }

    /**
     * @param \PhpParser\Node $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if ($node instanceof \PhpParser\Node\Stmt\ClassLike) {
            return $this->declarationErrors($node, $scope);
        }

        if (!$node instanceof \PhpParser\Node\Expr) {
            return [];
        }

        $callerNamespace = $scope->getNamespace() ?? '';
        if ($this->exemptCallerNamespaces->contains($callerNamespace)) {
            return [];
        }

        return $this->usageInspector->errors($node, $scope, $callerNamespace);
    }

    /**
     * Returns the tag errors and the out-of-scope type references of one declaration.
     *
     * Tag errors are reported even inside an exempt namespace: a tag that cannot be
     * honoured is wrong wherever it is written.
     *
     * @return list<IdentifierRuleError>
     */
    public function declarationErrors(\PhpParser\Node\Stmt\ClassLike $node, Scope $scope): array
    {
        $callerNamespace = $scope->getNamespace() ?? '';
        $errors = isset($node->namespacedName) ? $this->tagInspector->errors($node, $node->namespacedName->toString()) : [];

        if ($this->exemptCallerNamespaces->contains($callerNamespace)) {
            return $errors;
        }

        return array_merge($errors, $this->typeReferenceInspector->errors($node, $scope, $callerNamespace));
    }
}
