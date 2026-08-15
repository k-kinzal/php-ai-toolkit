<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render;

use function explode;
use function implode;
use function in_array;

use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeForParameterNode;
use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeNode;
use PHPStan\PhpDocParser\Ast\Type\OffsetAccessTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

use function sprintf;
use function str_contains;
use function str_starts_with;
use function strrpos;
use function strtolower;
use function substr;

/**
 * Renders complete type expressions as linked, styled HTML.
 *
 * PHPDoc types take precedence over native declarations; every class name
 * that resolves to a documented symbol links to its page, and generic
 * parameters and type aliases in scope are styled distinctly.
 */
final class TypeHtml
{
    /** @var list<string> */
    private const KEYWORDS = [
        'int', 'integer', 'float', 'double', 'string', 'bool', 'boolean', 'true', 'false', 'null', 'mixed',
        'void', 'never', 'never-return', 'never-returns', 'no-return', 'object', 'callable', 'iterable', 'array', 'list', 'non-empty-array',
        'non-empty-list', 'scalar', 'numeric', 'array-key', 'positive-int', 'negative-int', 'non-positive-int',
        'non-negative-int', 'non-zero-int', 'non-empty-string', 'non-falsy-string', 'truthy-string', 'literal-string',
        'numeric-string', 'class-string', 'interface-string', 'trait-string', 'enum-string', 'callable-string',
        'lowercase-string', 'uppercase-string', 'resource', 'closed-resource', 'open-resource', 'self', 'static',
        'parent', 'key-of', 'value-of', 'int-mask', 'int-mask-of', 'pure-callable', 'pure-closure', 'string-alias',
    ];

    /** @readonly */
    private HtmlText $escaper;

    /** @readonly */
    private SiteUrl $url;

    /**
     * Creates a type renderer from escaping and link collaborators.
     */
    public function __construct(?HtmlText $escaper = null, ?SiteUrl $url = null)
    {
        $this->escaper = $escaper ?? new HtmlText();
        $this->url = $url ?? new SiteUrl();
    }

    /**
     * Renders the most precise type available for a declaration site.
     */
    public function render(?TypeNode $annotated, ?string $native, TypeRenderContext $context): string
    {
        if ($annotated !== null) {
            return $this->node($annotated, $context);
        }

        if ($native !== null) {
            return $this->nativeString($native, $context);
        }

        return '';
    }

    /**
     * Renders one PHPDoc type node.
     */
    public function node(TypeNode $type, TypeRenderContext $context): string
    {
        if ($type instanceof IdentifierTypeNode) {
            return $this->identifier($type, $context);
        }

        if ($type instanceof NullableTypeNode) {
            return '?' . $this->node($type->type, $context);
        }

        if ($type instanceof UnionTypeNode || $type instanceof IntersectionTypeNode) {
            $parts = [];
            foreach ($type->types as $member) {
                $inner = $this->node($member, $context);
                $parts[] = $member instanceof UnionTypeNode || $member instanceof IntersectionTypeNode ? '(' . $inner . ')' : $inner;
            }

            return implode($type instanceof UnionTypeNode ? '|' : '&amp;', $parts);
        }

        if ($type instanceof GenericTypeNode) {
            return $this->generic($type, $context);
        }

        if ($type instanceof ArrayTypeNode) {
            return $this->node($type->type, $context) . '[]';
        }

        if ($type instanceof ArrayShapeNode) {
            return $this->arrayShape($type, $context);
        }

        if ($type instanceof ObjectShapeNode) {
            return $this->objectShape($type, $context);
        }

        if ($type instanceof CallableTypeNode) {
            return $this->callableType($type, $context);
        }

        if ($type instanceof ConditionalTypeNode || $type instanceof ConditionalTypeForParameterNode) {
            return $this->conditional($type, $context);
        }

        return $this->miscNode($type, $context);
    }

    /**
     * Renders the rarer type nodes and the escaped fallback.
     */
    public function miscNode(TypeNode $type, TypeRenderContext $context): string
    {
        if ($type instanceof ConstTypeNode) {
            return '<span class="t-lit">' . $this->escaper->e((string) $type->constExpr) . '</span>';
        }

        if ($type instanceof ThisTypeNode) {
            return '<span class="t-key">$this</span>';
        }

        if ($type instanceof OffsetAccessTypeNode) {
            return $this->node($type->type, $context) . '[' . $this->node($type->offset, $context) . ']';
        }

        return $this->escaper->e((string) $type);
    }

    /**
     * Renders one identifier, linking documented class names.
     */
    public function identifier(IdentifierTypeNode $type, TypeRenderContext $context): string
    {
        $name = $type->name;
        if (in_array(strtolower($name), self::KEYWORDS, true)) {
            return '<span class="t-key">' . $this->escaper->e($name) . '</span>';
        }

        if (in_array($name, $context->templates, true)) {
            return '<span class="t-gen">' . $this->escaper->e($name) . '</span>';
        }

        if (isset($context->aliases[$name])) {
            return sprintf('<a class="t-alias" href="%s">%s</a>', $this->escaper->e($context->aliases[$name]), $this->escaper->e($name));
        }

        return $this->className($name, $context);
    }

    /**
     * Renders a class name, linked when the symbol is documented.
     */
    public function className(string $written, TypeRenderContext $context): string
    {
        $fqcn = $this->resolve($written, $context);
        $target = $context->symbolTable->classLike($fqcn);
        $separator = strrpos($fqcn, '\\');
        $short = $separator === false ? $fqcn : substr($fqcn, $separator + 1);
        if ($target instanceof ClassLikeDoc) {
            return sprintf(
                '<a class="t-name k-%s" href="%s" title="%s">%s</a>',
                $target->kind,
                $this->escaper->e($this->url->href($context->pagePath, $this->url->classLikePage($target))),
                $this->escaper->e($target->fqcn),
                $this->escaper->e($short),
            );
        }

        return sprintf('<span class="t-ext" title="%s">%s</span>', $this->escaper->e($fqcn), $this->escaper->e($short));
    }

    /**
     * Resolves a written type name to its fully qualified form.
     */
    public function resolve(string $written, TypeRenderContext $context): string
    {
        if (str_starts_with($written, '\\')) {
            return substr($written, 1);
        }

        $first = $written;
        $rest = '';
        if (str_contains($written, '\\')) {
            [$first, $rest] = explode('\\', $written, 2);
            $rest = '\\' . $rest;
        }

        $imported = $context->useMap[strtolower($first)] ?? null;
        if ($imported !== null) {
            return $imported . $rest;
        }

        if ($context->namespace !== '' && $context->symbolTable->classLike($context->namespace . '\\' . $written) !== null) {
            return $context->namespace . '\\' . $written;
        }

        if ($context->symbolTable->classLike($written) !== null) {
            return $written;
        }

        return $context->namespace !== '' ? $context->namespace . '\\' . $written : $written;
    }

    /**
     * Renders a generic type such as list of T or class-string of T.
     */
    public function generic(GenericTypeNode $type, TypeRenderContext $context): string
    {
        $arguments = [];
        foreach ($type->genericTypes as $position => $argument) {
            $variance = $type->variances[$position] ?? GenericTypeNode::VARIANCE_INVARIANT;
            $prefix = $variance !== GenericTypeNode::VARIANCE_INVARIANT ? '<span class="t-key">' . $this->escaper->e($variance) . '</span> ' : '';
            $arguments[] = $prefix . $this->node($argument, $context);
        }

        return $this->identifier($type->type, $context) . '&lt;' . implode(', ', $arguments) . '&gt;';
    }

    /**
     * Renders an array shape with its keyed items.
     */
    public function arrayShape(ArrayShapeNode $type, TypeRenderContext $context): string
    {
        $items = [];
        foreach ($type->items as $item) {
            $prefix = '';
            if ($item->keyName !== null) {
                $prefix = '<span class="t-shape-key">' . $this->escaper->e((string) $item->keyName) . '</span>' . ($item->optional ? '?' : '') . ': ';
            }

            $items[] = $prefix . $this->node($item->valueType, $context);
        }

        if (!$type->sealed) {
            $items[] = '...';
        }

        return '<span class="t-key">' . $this->escaper->e($type->kind) . '</span>{' . implode(', ', $items) . '}';
    }

    /**
     * Renders an object shape with its keyed properties.
     */
    public function objectShape(ObjectShapeNode $type, TypeRenderContext $context): string
    {
        $items = [];
        foreach ($type->items as $item) {
            $items[] = '<span class="t-shape-key">' . $this->escaper->e((string) $item->keyName) . '</span>'
                . ($item->optional ? '?' : '') . ': ' . $this->node($item->valueType, $context);
        }

        return '<span class="t-key">object</span>{' . implode(', ', $items) . '}';
    }

    /**
     * Renders a callable type with parameters and return type.
     */
    public function callableType(CallableTypeNode $type, TypeRenderContext $context): string
    {
        $parameters = [];
        foreach ($type->parameters as $parameter) {
            $text = $this->node($parameter->type, $context);
            if ($parameter->isReference) {
                $text .= '&amp;';
            }

            if ($parameter->isVariadic) {
                $text .= '...';
            }

            if ($parameter->parameterName !== '') {
                $text .= ' ' . $this->escaper->e($parameter->parameterName);
            }

            if ($parameter->isOptional) {
                $text .= '=';
            }

            $parameters[] = $text;
        }

        return $this->identifier($type->identifier, $context)
            . '(' . implode(', ', $parameters) . '): ' . $this->node($type->returnType, $context);
    }

    /**
     * Renders a conditional type expression.
     *
     * @param ConditionalTypeNode|ConditionalTypeForParameterNode $type
     */
    public function conditional(TypeNode $type, TypeRenderContext $context): string
    {
        $subject = $type instanceof ConditionalTypeNode
            ? $this->node($type->subjectType, $context)
            : '<span class="t-var">' . $this->escaper->e($type->parameterName) . '</span>';

        return '(' . $subject . ' <span class="t-key">is</span> '
            . ($type->negated ? '<span class="t-key">not</span> ' : '')
            . $this->node($type->targetType, $context)
            . ' ? ' . $this->node($type->if, $context)
            . ' : ' . $this->node($type->else, $context) . ')';
    }

    /**
     * Renders a native type declaration string with linked class names.
     */
    public function nativeString(string $native, TypeRenderContext $context): string
    {
        $nullable = str_starts_with($native, '?');
        $bare = $nullable ? substr($native, 1) : $native;
        $unionParts = [];
        foreach (explode('|', $bare) as $unionPart) {
            $intersectionParts = [];
            foreach (explode('&', $unionPart) as $piece) {
                if ($piece === '') {
                    continue;
                }

                $intersectionParts[] = in_array(strtolower($piece), self::KEYWORDS, true)
                    ? '<span class="t-key">' . $this->escaper->e($piece) . '</span>'
                    : $this->className($piece, $context);
            }

            $unionParts[] = implode('&amp;', $intersectionParts);
        }

        return ($nullable ? '?' : '') . implode('|', $unionParts);
    }
}
