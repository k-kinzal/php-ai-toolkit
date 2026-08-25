<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\PhpDoc;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;
use function stripos;

/**
 * Requires list<V> when a property or callable visibly owns a list literal.
 *
 * @implements Rule<Stmt>
 */
final class RequireListForArrayLiteralRule implements Rule
{
    /** @readonly */
    private ListTypeDeclarationInspector $declarations;

    /** @readonly */
    private FixedListExpressionInspector $expressions;

    /**
     * Creates the rule from PHPDoc and literal expression inspection.
     */
    public function __construct(
        ?ListTypeDeclarationInspector $declarations = null,
        ?FixedListExpressionInspector $expressions = null,
    ) {
        $this->declarations = $declarations ?? new ListTypeDeclarationInspector();
        $this->expressions = $expressions ?? new FixedListExpressionInspector();
    }

    /**
     * @return class-string<Stmt>
     */
    public function getNodeType(): string
    {
        return Stmt::class;
    }

    /**
     * @param Stmt $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if ($node instanceof Property) {
            return $this->propertyErrors($node);
        }
        if ($node instanceof ClassMethod || $node instanceof Function_) {
            return $this->returnErrors($node);
        }

        return [];
    }

    /**
     * Builds errors for list-valued property initializers.
     *
     * @return list<IdentifierRuleError>
     */
    public function propertyErrors(Property $property): array
    {
        $docComment = $property->getDocComment();
        if ($docComment === null || stripos($docComment->getText(), 'array') === false) {
            return [];
        }

        $declarations = $this->declarations->propertyDeclarations($docComment->getText());
        $errors = [];
        foreach ($property->props as $item) {
            if (!$item->default instanceof Array_ || !$this->expressions->isNonEmptyList($item->default)) {
                continue;
            }
            $propertyName = '$' . $item->name->toString();
            foreach ($declarations as $declaration) {
                if ($declaration['variable'] !== '' && $declaration['variable'] !== $propertyName) {
                    continue;
                }
                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Replace "%s" with "%s" in %s on %s; %s is initialized with a non-empty list literal, so declare its zero-based contiguous keys as part of the property type.',
                    $declaration['type'],
                    $declaration['replacement'],
                    $declaration['tag'],
                    $propertyName,
                    $propertyName
                ))
                    ->identifier('customRules.arrayLiteralListType')
                    ->line($property->getStartLine())
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * Builds errors for list-valued callable returns.
     *
     * @return list<IdentifierRuleError>
     */
    public function returnErrors(ClassMethod|Function_ $callable): array
    {
        $docComment = $callable->getDocComment();
        if ($docComment === null || stripos($docComment->getText(), 'array') === false) {
            return [];
        }
        if (!$this->expressions->callableReturnsFixedLists($callable)) {
            return [];
        }

        $callableName = sprintf('%s()', $callable->name->toString());
        $errors = [];
        foreach ($this->declarations->returnDeclarations($docComment->getText()) as $declaration) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                'Replace "%s" with "%s" in %s on %s; %s returns a non-empty list literal, so declare its zero-based contiguous keys as part of the return type.',
                $declaration['type'],
                $declaration['replacement'],
                $declaration['tag'],
                $callableName,
                $callableName
            ))
                ->identifier('customRules.arrayLiteralListType')
                ->line($callable->getStartLine())
                ->build();
        }

        return $errors;
    }
}
