<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Parse\Builder;

use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeKind;
use PhpAiToolkit\DocGen\Analysis\Model\PropertyDoc;
use PhpAiToolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\SymbolContext;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\EnumCase;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUse;

/**
 * Builds class-like models from php-parser declarations.
 */
final class ClassLikeBuilder
{
    /** @readonly */
    private DocBlockReader $docBlockReader;

    /** @readonly */
    private MethodBuilder $methodBuilder;

    /** @readonly */
    private PropertyBuilder $propertyBuilder;

    /** @readonly */
    private ConstantBuilder $constantBuilder;

    /** @readonly */
    private EnumCaseBuilder $enumCaseBuilder;

    /** @readonly */
    private NativeTypePrinter $typePrinter;

    /**
     * Creates a class-like builder from member building collaborators.
     */
    public function __construct(
        ?DocBlockReader $docBlockReader = null,
        ?MethodBuilder $methodBuilder = null,
        ?PropertyBuilder $propertyBuilder = null,
        ?ConstantBuilder $constantBuilder = null,
        ?EnumCaseBuilder $enumCaseBuilder = null,
        ?NativeTypePrinter $typePrinter = null,
    ) {
        $this->docBlockReader = $docBlockReader ?? new DocBlockReader();
        $this->methodBuilder = $methodBuilder ?? new MethodBuilder();
        $this->propertyBuilder = $propertyBuilder ?? new PropertyBuilder();
        $this->constantBuilder = $constantBuilder ?? new ConstantBuilder();
        $this->enumCaseBuilder = $enumCaseBuilder ?? new EnumCaseBuilder();
        $this->typePrinter = $typePrinter ?? new NativeTypePrinter();
    }

    /**
     * Builds one class-like model, or null for anonymous declarations.
     */
    public function build(ClassLike $node, SymbolContext $context): ?ClassLikeDoc
    {
        if ($node->name === null) {
            return null;
        }

        $shortName = $node->name->toString();
        $fqcn = $node->namespacedName !== null
            ? $node->namespacedName->toString()
            : ($context->namespace !== '' ? $context->namespace . '\\' . $shortName : $shortName);
        $docComment = $node->getDocComment();
        $members = $this->members($node);
        $parents = $this->parents($node);

        return new ClassLikeDoc(
            $fqcn,
            $shortName,
            $context->namespace,
            $this->kindOf($node),
            $context->packageName,
            $context->file,
            $node->getStartLine(),
            $node->getEndLine(),
            $node instanceof Class_ && $node->isAbstract(),
            $node instanceof Class_ && $node->isFinal(),
            $parents['extends'],
            $parents['implements'],
            $this->traitNames($node),
            $members['constants'],
            $members['properties'],
            $members['methods'],
            $members['cases'],
            $parents['backing'],
            $this->docBlockReader->read($docComment !== null ? $docComment->getText() : null),
            $context->useMap,
            $context->isDev,
        );
    }

    /**
     * Returns the model kind of a class-like node.
     */
    public function kindOf(ClassLike $node): string
    {
        if ($node instanceof Interface_) {
            return ClassLikeKind::INTERFACE_;
        }

        if ($node instanceof Trait_) {
            return ClassLikeKind::TRAIT_;
        }

        if ($node instanceof Enum_) {
            return ClassLikeKind::ENUM_;
        }

        return ClassLikeKind::CLASS_;
    }

    /**
     * Collects the parent class, interfaces, and enum backing type.
     *
     * @return array{extends: list<string>, implements: list<string>, backing: ?string}
     */
    public function parents(ClassLike $node): array
    {
        $extends = [];
        $implements = [];
        $backing = null;
        if ($node instanceof Class_) {
            if ($node->extends !== null) {
                $extends[] = $node->extends->toString();
            }

            foreach ($node->implements as $name) {
                $implements[] = $name->toString();
            }
        }

        if ($node instanceof Interface_) {
            foreach ($node->extends as $name) {
                $extends[] = $name->toString();
            }
        }

        if ($node instanceof Enum_) {
            foreach ($node->implements as $name) {
                $implements[] = $name->toString();
            }

            $backing = $this->typePrinter->print($node->scalarType);
        }

        return ['extends' => $extends, 'implements' => $implements, 'backing' => $backing];
    }

    /**
     * Collects the used trait names of a class-like node.
     *
     * @return list<string>
     */
    public function traitNames(ClassLike $node): array
    {
        $traits = [];
        foreach ($node->stmts as $statement) {
            if ($statement instanceof TraitUse) {
                foreach ($statement->traits as $name) {
                    $traits[] = $name->toString();
                }
            }
        }

        return $traits;
    }

    /**
     * Builds all member models of a class-like node.
     *
     * Constructor-promoted parameters are added to the property list so the
     * documented property surface is complete.
     *
     * @return array{constants: list<\PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc>, properties: list<PropertyDoc>, methods: list<\PhpAiToolkit\DocGen\Analysis\Model\MethodDoc>, cases: list<\PhpAiToolkit\DocGen\Analysis\Model\EnumCaseDoc>}
     */
    public function members(ClassLike $node): array
    {
        $constants = [];
        $properties = [];
        $methods = [];
        $cases = [];
        foreach ($node->stmts as $statement) {
            if ($statement instanceof ClassConst) {
                foreach ($this->constantBuilder->build($statement) as $constant) {
                    $constants[] = $constant;
                }
            } elseif ($statement instanceof Property) {
                foreach ($this->propertyBuilder->build($statement) as $property) {
                    $properties[] = $property;
                }
            } elseif ($statement instanceof EnumCase) {
                $cases[] = $this->enumCaseBuilder->build($statement);
            } elseif ($statement instanceof ClassMethod) {
                $method = $this->methodBuilder->build($statement);
                $methods[] = $method;
                if ($statement->name->toLowerString() === '__construct') {
                    foreach ($this->promotedProperties($method->parameters, $statement) as $property) {
                        $properties[] = $property;
                    }
                }
            }
        }

        return ['constants' => $constants, 'properties' => $properties, 'methods' => $methods, 'cases' => $cases];
    }

    /**
     * Builds property models for constructor-promoted parameters.
     *
     * @param list<\PhpAiToolkit\DocGen\Analysis\Model\ParameterDoc> $parameters
     *
     * @return list<PropertyDoc>
     */
    public function promotedProperties(array $parameters, ClassMethod $constructor): array
    {
        $properties = [];
        foreach ($parameters as $index => $parameter) {
            if ($parameter->promotedVisibility === null) {
                continue;
            }

            $line = isset($constructor->params[$index]) ? $constructor->params[$index]->getStartLine() : $constructor->getStartLine();
            $properties[] = new PropertyDoc(
                $parameter->name,
                $parameter->promotedVisibility,
                false,
                true,
                $parameter->type,
                $parameter->defaultText,
                null,
                $line,
            );
        }

        return $properties;
    }
}
