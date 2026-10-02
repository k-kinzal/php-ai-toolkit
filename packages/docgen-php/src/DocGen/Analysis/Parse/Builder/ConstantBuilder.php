<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Parse\Builder;

use PhpParser\Node\Stmt\ClassConst;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Model\ConstantDoc;
use Toolkit\DocGen\Analysis\Parse\ExprTextPrinter;

/**
 * Builds constant models from php-parser class constant nodes.
 */
final class ConstantBuilder
{
    /** @readonly */
    private DocBlockReader $docBlockReader;

    /** @readonly */
    private ExprTextPrinter $exprPrinter;

    /**
     * Creates a constant builder from parsing collaborators.
     */
    public function __construct(
        ?DocBlockReader $docBlockReader = null,
        ?ExprTextPrinter $exprPrinter = null,
    ) {
        $this->docBlockReader = $docBlockReader ?? new DocBlockReader();
        $this->exprPrinter = $exprPrinter ?? new ExprTextPrinter();
    }

    /**
     * Builds the constant models declared by one class constant statement.
     *
     * @return list<ConstantDoc>
     */
    public function build(ClassConst $constant): array
    {
        $docComment = $constant->getDocComment();
        $docBlock = $this->docBlockReader->read($docComment !== null ? $docComment->getText() : null);
        $visibility = 'public';
        if ($constant->isProtected()) {
            $visibility = 'protected';
        }

        if ($constant->isPrivate()) {
            $visibility = 'private';
        }

        $constants = [];
        foreach ($constant->consts as $item) {
            $constants[] = new ConstantDoc(
                $item->name->toString(),
                $visibility,
                $this->exprPrinter->print($item->value),
                $docBlock,
                $item->getStartLine(),
            );
        }

        return $constants;
    }
}
