<?php

declare(strict_types=1);

namespace Toolkit\Rector;

use Override;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use Rector\Rector\AbstractRector;
use Toolkit\PhpStan\Rule\PhpDoc\FixedListExpressionInspector;
use Toolkit\PhpStan\Rule\PhpDoc\ListTypeDeclarationInspector;
use Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser;

/**
 * Narrows literal-owned array declarations to list contracts for review.
 */
final class RequireListForArrayLiteralRector extends AbstractRector
{
    /**
     * @return list<class-string<Node>>
     */
    #[Override]
    public function getNodeTypes(): array
    {
        return [Property::class, ClassMethod::class, Function_::class];
    }

    /**
     * Changes only owned return or property types, preserving parameter types.
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof Property && !$node instanceof ClassMethod && !$node instanceof Function_) {
            return null;
        }

        $doc = $node->getDocComment();
        $expressions = new FixedListExpressionInspector();
        if ($doc === null) {
            return null;
        }
        if ($node instanceof Property) {
            if (count($node->props) !== 1 || !$node->props[0]->default instanceof Array_ || !$expressions->isNonEmptyList($node->props[0]->default)) {
                return null;
            }
        } elseif (!$expressions->callableReturnsFixedLists($node)) {
            return null;
        }

        $parsed = (new RulePhpDocParser())->parse($doc->getText());
        $before = (string) $parsed;
        $tagNames = $node instanceof Property ? ['@var', '@phpstan-var', '@psalm-var'] : ['@return', '@phpstan-return', '@psalm-return'];
        foreach ($tagNames as $tagName) {
            foreach ($parsed->getTagsByName($tagName) as $tag) {
                if ($tag->value instanceof VarTagValueNode && $node instanceof Property) {
                    if ($tag->value->variableName !== '' && $tag->value->variableName !== '$' . $node->props[0]->name->toString()) {
                        continue;
                    }
                    $this->narrow($tag->value->type);
                } elseif ($tag->value instanceof ReturnTagValueNode) {
                    $this->narrow($tag->value->type);
                }
            }
        }
        if ((string) $parsed === $before) {
            return null;
        }
        $node->setDocComment(new Doc((string) $parsed));

        return $node;
    }

    /**
     * Rewrites direct union/intersection branches without changing nested value types.
     */
    public function narrow(TypeNode $type): void
    {
        if ($type instanceof GenericTypeNode && (new ListTypeDeclarationInspector())->isArrayIntType($type)) {
            $type->type = new IdentifierTypeNode('list');
            $type->genericTypes = [$type->genericTypes[1]];
            $type->variances = [$type->variances[1] ?? GenericTypeNode::VARIANCE_INVARIANT];
        } elseif ($type instanceof NullableTypeNode) {
            $this->narrow($type->type);
        } elseif ($type instanceof UnionTypeNode || $type instanceof IntersectionTypeNode) {
            foreach ($type->types as $member) {
                $this->narrow($member);
            }
        }
    }
}
