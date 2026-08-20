<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\NamespaceVisibility;

use function array_merge;
use function ltrim;

use PhpAiToolkit\PhpStan\Rule\Shared\ClassLikeKindLabel;
use PHPStan\Rules\IdentifierRuleError;

use function sprintf;

/**
 * Reports @visibility tags that cannot be honoured as written.
 *
 * A scope that resolves to nothing is left unenforced on purpose, so a mistyped tag
 * has to be reported here or it would silently drop the restriction it was meant to add.
 */
final class VisibilityTagInspector
{
    /** @readonly */
    private VisibilityTagParser $tagParser;

    /** @readonly */
    private VisibilityScopeResolver $scopeResolver;

    /** @readonly */
    private NamespaceLineage $lineage;

    /** @readonly */
    private VisibilityErrorBuilder $errorBuilder;

    /** @readonly */
    private ClassLikeKindLabel $kindLabel;

    /**
     * Creates the inspector from tag reading, scope resolution, namespace ancestry, and error building.
     */
    public function __construct(
        ?VisibilityTagParser $tagParser = null,
        ?VisibilityScopeResolver $scopeResolver = null,
        ?NamespaceLineage $lineage = null,
        ?VisibilityErrorBuilder $errorBuilder = null,
        ?ClassLikeKindLabel $kindLabel = null,
    ) {
        $this->tagParser = $tagParser ?? new VisibilityTagParser();
        $this->scopeResolver = $scopeResolver ?? new VisibilityScopeResolver();
        $this->lineage = $lineage ?? new NamespaceLineage();
        $this->errorBuilder = $errorBuilder ?? new VisibilityErrorBuilder();
        $this->kindLabel = $kindLabel ?? new ClassLikeKindLabel();
    }

    /**
     * Returns the tag errors of a class-like and of every member it declares.
     *
     * @return list<IdentifierRuleError>
     */
    public function errors(\PhpParser\Node\Stmt\ClassLike $node, string $className): array
    {
        $namespace = $this->lineage->of($className);
        $subject = sprintf('%s %s', $this->kindLabel->label($node), $className);

        return array_merge(
            $this->tagErrors($this->docCommentOf($node), $namespace, $subject, $node->getStartLine()),
            $this->methodErrors($node, $className, $namespace),
            $this->propertyErrors($node, $className, $namespace),
            $this->constantErrors($node, $className, $namespace)
        );
    }

    /**
     * Returns the tag errors of every method a class-like declares.
     *
     * @return list<IdentifierRuleError>
     */
    public function methodErrors(\PhpParser\Node\Stmt\ClassLike $node, string $className, string $namespace): array
    {
        $errors = [];
        foreach ($node->getMethods() as $method) {
            $subject = sprintf('method %s::%s()', $className, $method->name->toString());
            $errors = array_merge($errors, $this->tagErrors($this->docCommentOf($method), $namespace, $subject, $method->getStartLine()));
        }

        return $errors;
    }

    /**
     * Returns the tag errors of every property a class-like declares.
     *
     * @return list<IdentifierRuleError>
     */
    public function propertyErrors(\PhpParser\Node\Stmt\ClassLike $node, string $className, string $namespace): array
    {
        $errors = [];
        foreach ($node->getProperties() as $property) {
            foreach ($property->props as $declaredProperty) {
                $subject = sprintf('property %s::$%s', $className, $declaredProperty->name->toString());
                $errors = array_merge($errors, $this->tagErrors($this->docCommentOf($property), $namespace, $subject, $property->getStartLine()));
            }
        }

        return $errors;
    }

    /**
     * Returns the tag errors of every class constant and enum case a class-like declares.
     *
     * @return list<IdentifierRuleError>
     */
    public function constantErrors(\PhpParser\Node\Stmt\ClassLike $node, string $className, string $namespace): array
    {
        $errors = [];
        foreach ($node->getConstants() as $classConstant) {
            foreach ($classConstant->consts as $declaredConstant) {
                $subject = sprintf('constant %s::%s', $className, $declaredConstant->name->toString());
                $errors = array_merge($errors, $this->tagErrors($this->docCommentOf($classConstant), $namespace, $subject, $classConstant->getStartLine()));
            }
        }

        foreach ($node->stmts as $statement) {
            if ($statement instanceof \PhpParser\Node\Stmt\EnumCase) {
                $subject = sprintf('enum case %s::%s', $className, $statement->name->toString());
                $errors = array_merge($errors, $this->tagErrors($this->docCommentOf($statement), $namespace, $subject, $statement->getStartLine()));
            }
        }

        return $errors;
    }

    /**
     * Returns the errors of the @visibility tags on one PHPDoc comment.
     *
     * @return list<IdentifierRuleError>
     */
    public function tagErrors(?string $docComment, string $declaringNamespace, string $subject, int $line): array
    {
        $errors = [];
        $hasPublic = false;
        $hasNarrowing = false;

        foreach ($this->tagParser->values($docComment) as $value) {
            $keyword = $this->scopeResolver->keywordOf($value);
            if ($keyword === 'public') {
                $hasPublic = true;
                continue;
            }

            $hasNarrowing = true;
            $reason = $this->reasonFor($value, $keyword, $declaringNamespace);
            if ($reason !== null) {
                $errors[] = $this->errorBuilder->tagProblem($subject, $value, $reason, $line);
            }
        }

        if ($hasPublic && $hasNarrowing) {
            $errors[] = $this->errorBuilder->contradictoryTags($subject, $line);
        }

        return $errors;
    }

    /**
     * Returns why a narrowing scope value cannot be honoured, or null when it can.
     */
    public function reasonFor(string $value, ?string $keyword, string $declaringNamespace): ?string
    {
        if ($keyword === 'namespace') {
            return $declaringNamespace === ''
                ? 'the declaration is in the global namespace, so "namespace" covers every namespace instead of narrowing anything'
                : null;
        }

        if ($keyword === 'parent') {
            $parent = $this->lineage->parentOf($declaringNamespace);
            if ($parent === null) {
                return 'the declaration is in the global namespace, which has no parent namespace to open up';
            }

            return $parent === ''
                ? sprintf('the parent of namespace "%s" is the global namespace, which narrows nothing; write "@visibility namespace" or name an outer namespace', $declaringNamespace)
                : null;
        }

        if ($keyword === 'root') {
            return $this->lineage->rootOf($declaringNamespace) === null
                ? 'the declaration is in the global namespace, which has no root namespace to open up'
                : null;
        }

        if (!$this->scopeResolver->isNamespaceName(ltrim($value, '\\'))) {
            return 'the scope has to be "public", "root", "parent", "namespace", or a namespace name such as "App\\Domain"';
        }

        return $this->scopeResolver->isKeywordShape($value)
            ? sprintf('one bare lowercase word is read as a scope keyword, and "%s" is not one of "public", "root", "parent", "namespace"; write the keyword you meant, or write "\\%s" to name the namespace', $value, $value)
            : null;
    }

    /**
     * Returns the raw PHPDoc text of a node, or null when it carries none.
     */
    public function docCommentOf(\PhpParser\Node $node): ?string
    {
        $docComment = $node->getDocComment();

        return $docComment === null ? null : $docComment->getText();
    }
}
