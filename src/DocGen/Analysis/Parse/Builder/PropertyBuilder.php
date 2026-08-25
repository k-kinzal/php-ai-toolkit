<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Parse\Builder;

use PhpParser\Node\Stmt\Property;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Model\PropertyDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;
use Toolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use Toolkit\DocGen\Analysis\Parse\NativeTypePrinter;

/**
 * Builds property models from php-parser property nodes.
 */
final class PropertyBuilder
{
    /** @readonly */
    private DocBlockReader $docBlockReader;

    /** @readonly */
    private NativeTypePrinter $typePrinter;

    /** @readonly */
    private ExprTextPrinter $exprPrinter;

    /**
     * Creates a property builder from parsing collaborators.
     */
    public function __construct(
        ?DocBlockReader $docBlockReader = null,
        ?NativeTypePrinter $typePrinter = null,
        ?ExprTextPrinter $exprPrinter = null,
    ) {
        $this->docBlockReader = $docBlockReader ?? new DocBlockReader();
        $this->typePrinter = $typePrinter ?? new NativeTypePrinter();
        $this->exprPrinter = $exprPrinter ?? new ExprTextPrinter();
    }

    /**
     * Builds the property models declared by one property statement.
     *
     * @return list<PropertyDoc>
     */
    public function build(Property $property): array
    {
        $docComment = $property->getDocComment();
        $docBlock = $this->docBlockReader->read($docComment !== null ? $docComment->getText() : null);
        $visibility = 'public';
        if ($property->isProtected()) {
            $visibility = 'protected';
        }

        if ($property->isPrivate()) {
            $visibility = 'private';
        }

        $nativeType = $this->typePrinter->print($property->type);
        $properties = [];
        foreach ($property->props as $item) {
            $properties[] = new PropertyDoc(
                $item->name->toString(),
                $visibility,
                $property->isStatic(),
                false,
                new TypeSignature($nativeType, $docBlock !== null ? $docBlock->var : null),
                $this->exprPrinter->print($item->default),
                $docBlock,
                $item->getStartLine(),
            );
        }

        return $properties;
    }
}
