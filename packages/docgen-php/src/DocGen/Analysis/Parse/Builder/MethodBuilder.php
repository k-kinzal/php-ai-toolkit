<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Parse\Builder;

use PhpParser\Node\Stmt\ClassMethod;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Model\MethodDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;
use Toolkit\DocGen\Analysis\Parse\NativeTypePrinter;

/**
 * Builds method models from php-parser class method nodes.
 */
final class MethodBuilder
{
    /** @readonly */
    private DocBlockReader $docBlockReader;

    /** @readonly */
    private ParameterBuilder $parameterBuilder;

    /** @readonly */
    private NativeTypePrinter $typePrinter;

    /**
     * Creates a method builder from parsing collaborators.
     */
    public function __construct(
        ?DocBlockReader $docBlockReader = null,
        ?ParameterBuilder $parameterBuilder = null,
        ?NativeTypePrinter $typePrinter = null,
    ) {
        $this->docBlockReader = $docBlockReader ?? new DocBlockReader();
        $this->parameterBuilder = $parameterBuilder ?? new ParameterBuilder();
        $this->typePrinter = $typePrinter ?? new NativeTypePrinter();
    }

    /**
     * Builds one method model.
     */
    public function build(ClassMethod $method): MethodDoc
    {
        $docComment = $method->getDocComment();
        $docBlock = $this->docBlockReader->read($docComment !== null ? $docComment->getText() : null);
        $parameters = [];
        foreach ($method->params as $param) {
            $parameters[] = $this->parameterBuilder->build($param, $docBlock);
        }

        $visibility = 'public';
        if ($method->isProtected()) {
            $visibility = 'protected';
        }

        if ($method->isPrivate()) {
            $visibility = 'private';
        }

        return new MethodDoc(
            $method->name->toString(),
            $visibility,
            $method->isStatic(),
            $method->isAbstract(),
            $method->isFinal(),
            $parameters,
            new TypeSignature($this->typePrinter->print($method->returnType), $docBlock !== null ? $docBlock->return : null),
            $docBlock,
            $method->getStartLine(),
            $method->getEndLine(),
        );
    }
}
