<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Parse\Builder;

use PhpParser\Node\Stmt\Property;
use Toolkit\DocGen\Parse\Doc\DocBlockReader;
use Toolkit\DocGen\Parse\ExprTextPrinter;
use Toolkit\DocGen\Parse\NativeTypePrinter;
use Toolkit\DocGen\Parse\Symbol\PropertyDoc;
use Toolkit\DocGen\Parse\Symbol\TypeSignature;

/**
 * Builds property models from php-parser property nodes.
 *
 * @visibility Toolkit\DocGen\Parse
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
