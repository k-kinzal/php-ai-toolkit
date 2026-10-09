<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Parse\Builder;

use PhpParser\Node\Stmt\EnumCase;
use Toolkit\DocGen\Parse\Doc\DocBlockReader;
use Toolkit\DocGen\Parse\ExprTextPrinter;
use Toolkit\DocGen\Parse\Symbol\EnumCaseDoc;

/**
 * Builds enum case models from php-parser enum case nodes.
 *
 * @visibility Toolkit\DocGen\Parse
 */
final class EnumCaseBuilder
{
    /** @readonly */
    private DocBlockReader $docBlockReader;

    /** @readonly */
    private ExprTextPrinter $exprPrinter;

    /**
     * Creates an enum case builder from parsing collaborators.
     */
    public function __construct(
        ?DocBlockReader $docBlockReader = null,
        ?ExprTextPrinter $exprPrinter = null,
    ) {
        $this->docBlockReader = $docBlockReader ?? new DocBlockReader();
        $this->exprPrinter = $exprPrinter ?? new ExprTextPrinter();
    }

    /**
     * Builds one enum case model.
     */
    public function build(EnumCase $case): EnumCaseDoc
    {
        $docComment = $case->getDocComment();

        return new EnumCaseDoc(
            $case->name->toString(),
            $this->exprPrinter->print($case->expr),
            $this->docBlockReader->read($docComment !== null ? $docComment->getText() : null),
            $case->getStartLine(),
        );
    }
}
