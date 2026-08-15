<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Parse;

use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PhpParser\Node\Stmt\Function_;

/**
 * Builds function models from php-parser function nodes.
 */
final class FunctionBuilder
{
    /** @readonly */
    private DocBlockReader $docBlockReader;

    /** @readonly */
    private ParameterBuilder $parameterBuilder;

    /** @readonly */
    private NativeTypePrinter $typePrinter;

    /**
     * Creates a function builder from parsing collaborators.
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
     * Builds one function model.
     */
    public function build(Function_ $function, SymbolContext $context): FunctionDoc
    {
        $docComment = $function->getDocComment();
        $docBlock = $this->docBlockReader->read($docComment !== null ? $docComment->getText() : null);
        $parameters = [];
        foreach ($function->params as $param) {
            $parameters[] = $this->parameterBuilder->build($param, $docBlock);
        }

        $shortName = $function->name->toString();

        return new FunctionDoc(
            $function->namespacedName !== null
                ? $function->namespacedName->toString()
                : ($context->namespace !== '' ? $context->namespace . '\\' . $shortName : $shortName),
            $shortName,
            $context->namespace,
            $context->packageName,
            $context->file,
            $function->getStartLine(),
            $function->getEndLine(),
            $parameters,
            new TypeSignature($this->typePrinter->print($function->returnType), $docBlock !== null ? $docBlock->return : null),
            $docBlock,
            $context->useMap,
            $context->isDev,
        );
    }
}
