<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Parse\Builder;

use function is_string;

use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use Toolkit\DocGen\Analysis\Model\DocBlock;
use Toolkit\DocGen\Analysis\Model\DocTag;
use Toolkit\DocGen\Analysis\Model\ParameterDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;
use Toolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use Toolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use Toolkit\DocGen\Analysis\Parse\ParameterModifiers;

/**
 * Builds parameter models from php-parser parameter nodes.
 */
final class ParameterBuilder
{
    /** @readonly */
    private NativeTypePrinter $typePrinter;

    /** @readonly */
    private ExprTextPrinter $exprPrinter;

    /** @readonly */
    private ParameterModifiers $modifiers;

    /**
     * Creates a parameter builder from printing collaborators.
     */
    public function __construct(
        ?NativeTypePrinter $typePrinter = null,
        ?ExprTextPrinter $exprPrinter = null,
        ?ParameterModifiers $modifiers = null,
    ) {
        $this->typePrinter = $typePrinter ?? new NativeTypePrinter();
        $this->exprPrinter = $exprPrinter ?? new ExprTextPrinter();
        $this->modifiers = $modifiers ?? new ParameterModifiers();
    }

    /**
     * Builds one parameter model, merging the matching param tag.
     */
    public function build(Param $param, ?DocBlock $docBlock): ParameterDoc
    {
        $name = $param->var instanceof Variable && is_string($param->var->name) ? $param->var->name : '';
        $annotated = null;
        if ($docBlock !== null) {
            $annotated = $docBlock->params['$' . $name] ?? null;
        }

        return new ParameterDoc(
            $name,
            new TypeSignature($this->typePrinter->print($param->type), $annotated),
            $param->byRef,
            $param->variadic,
            $this->exprPrinter->print($param->default),
            $this->modifiers->promotedVisibility($param->flags),
            $annotated instanceof DocTag ? $annotated->description : '',
        );
    }
}
