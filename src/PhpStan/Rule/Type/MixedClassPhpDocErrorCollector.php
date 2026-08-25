<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\Type;

use PhpAiToolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser;
use PHPStan\Node\InClassNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasTagValueNode;
use PHPStan\Rules\IdentifierRuleError;

use function sprintf;

/**
 * Collects mixed from restricted virtual members and local type aliases.
 *
 * @visibility namespace
 */
final class MixedClassPhpDocErrorCollector
{
    /** @readonly */
    private ConcreteMixedTypeInspector $typeInspector;

    /** @readonly */
    private PhpDocMixedTypeInspector $phpDocTypeInspector;

    /** @readonly */
    private MixedVisibilityDetector $visibilityDetector;

    /** @readonly */
    private MixedTypeErrorBuilder $errorBuilder;

    /** @readonly */
    private RulePhpDocParser $parser;

    /**
     * Creates the collector from resolved and syntactic type inspection.
     */
    public function __construct(
        ?ConcreteMixedTypeInspector $typeInspector = null,
        ?PhpDocMixedTypeInspector $phpDocTypeInspector = null,
        ?MixedVisibilityDetector $visibilityDetector = null,
        ?MixedTypeErrorBuilder $errorBuilder = null,
        ?RulePhpDocParser $parser = null,
    ) {
        $this->typeInspector = $typeInspector ?? new ConcreteMixedTypeInspector();
        $this->phpDocTypeInspector = $phpDocTypeInspector ?? new PhpDocMixedTypeInspector();
        $this->visibilityDetector = $visibilityDetector ?? new MixedVisibilityDetector();
        $this->errorBuilder = $errorBuilder ?? new MixedTypeErrorBuilder();
        $this->parser = $parser ?? new RulePhpDocParser();
    }

    /**
     * Collects errors from one restricted class-level PHPDoc contract.
     *
     * @return list<IdentifierRuleError>
     */
    public function errors(InClassNode $node): array
    {
        $class = $node->getClassReflection();
        if (!$this->visibilityDetector->classIsRestricted($class)) {
            return [];
        }

        $line = $node->getOriginalNode()->getStartLine();
        $errors = $this->propertyErrors($node, $line);
        foreach ($this->methodErrors($node, $line) as $error) {
            $errors[] = $error;
        }
        foreach ($this->typeAliasErrors($node, $line) as $error) {
            $errors[] = $error;
        }

        return $errors;
    }

    /**
     * Collects virtual-property errors.
     *
     * @return list<IdentifierRuleError>
     */
    public function propertyErrors(InClassNode $node, int $line): array
    {
        $class = $node->getClassReflection();
        $errors = [];
        foreach ($class->getPropertyTags() as $name => $tag) {
            $type = $tag->getReadableType();
            if ($type === null || !$this->typeInspector->contains($type)) {
                $type = $tag->getWritableType();
            }
            if ($type === null || !$this->typeInspector->contains($type)) {
                continue;
            }
            $errors[] = $this->errorBuilder->build(
                $this->typeInspector->describe($type),
                'virtual property type',
                sprintf('%s::$%s', $class->getDisplayName(), $name),
                $line
            );
        }

        return $errors;
    }

    /**
     * Collects virtual-method parameter and return errors.
     *
     * @return list<IdentifierRuleError>
     */
    public function methodErrors(InClassNode $node, int $line): array
    {
        $class = $node->getClassReflection();
        $errors = [];
        foreach ($class->getMethodTags() as $name => $tag) {
            foreach ($tag->getParameters() as $parameterName => $parameter) {
                $type = $parameter->getType();
                if ($this->typeInspector->contains($type)) {
                    $errors[] = $this->errorBuilder->build($this->typeInspector->describe($type), 'parameter $' . $parameterName, sprintf('%s::%s()', $class->getDisplayName(), $name), $line);
                }
            }
            $returnType = $tag->getReturnType();
            if ($this->typeInspector->contains($returnType)) {
                $errors[] = $this->errorBuilder->build($this->typeInspector->describe($returnType), 'return type', sprintf('%s::%s()', $class->getDisplayName(), $name), $line);
            }
        }

        return $errors;
    }

    /**
     * Collects local type-alias errors from raw PHPDoc syntax.
     *
     * @return list<IdentifierRuleError>
     */
    public function typeAliasErrors(InClassNode $node, int $line): array
    {
        $docComment = $node->getOriginalNode()->getDocComment();
        if ($docComment === null) {
            return [];
        }

        $class = $node->getClassReflection();
        $phpDoc = $this->parser->parse($docComment->getText());
        $errors = [];
        foreach (['@phpstan-type', '@psalm-type'] as $tagName) {
            foreach ($phpDoc->getTagsByName($tagName) as $tag) {
                if (!$tag->value instanceof TypeAliasTagValueNode || !$this->phpDocTypeInspector->contains($tag->value->type)) {
                    continue;
                }
                $errors[] = $this->errorBuilder->build((string) $tag->value->type, sprintf('type alias %s', $tag->value->alias), $class->getDisplayName(), $line);
            }
        }

        return $errors;
    }
}
