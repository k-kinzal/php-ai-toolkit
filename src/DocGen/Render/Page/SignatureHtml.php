<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use function count;
use function html_entity_decode;
use function implode;

use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeKind;
use PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc;
use PhpAiToolkit\DocGen\Analysis\Model\DocTag;
use PhpAiToolkit\DocGen\Analysis\Model\EnumCaseDoc;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;
use PhpAiToolkit\DocGen\Analysis\Model\MethodDoc;
use PhpAiToolkit\DocGen\Analysis\Model\PropertyDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TemplateDoc;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\TypeRenderContext;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

use function strcasecmp;
use function strip_tags;
use function strlen;

/**
 * Renders PHP declaration signatures with fully linked types.
 */
final class SignatureHtml
{
    /**
     * Signature length above which parameters wrap onto their own lines.
     */
    public const WRAP_LENGTH = 96;

    /**
     * Renders the declaration panel of a class-like symbol.
     */
    public function classSignature(RenderKit $services, ClassLikeDoc $classLike, TypeRenderContext $context): string
    {
        $escaper = $services->escaper;
        $keywords = [];
        if ($classLike->isAbstract) {
            $keywords[] = 'abstract';
        }

        if ($classLike->isFinal) {
            $keywords[] = 'final';
        }

        $keywords[] = $classLike->kind === ClassLikeKind::CLASS_ ? 'class' : $classLike->kind;
        $html = '<span class="t-key">' . implode('</span> <span class="t-key">', $keywords) . '</span> ';
        $html .= '<span class="sig-name">' . $escaper->e($classLike->shortName) . '</span>';
        $html .= $this->templateList($services, $classLike->docBlock !== null ? $classLike->docBlock->templates : [], $context);
        if ($classLike->backingType !== null) {
            $html .= ': <span class="t-key">' . $escaper->e($classLike->backingType) . '</span>';
        }

        $html .= $this->parentClause($services, 'extends', $classLike->extends, $classLike->docBlock !== null ? $classLike->docBlock->extendsTags : [], $context);
        $html .= $this->parentClause($services, 'implements', $classLike->implements, $classLike->docBlock !== null ? $classLike->docBlock->implementsTags : [], $context);
        $html .= $this->parentClause($services, 'uses', $classLike->traits, $classLike->docBlock !== null ? $classLike->docBlock->usesTags : [], $context);

        return '<pre class="signature"><code>' . $html . '</code></pre>' . "\n";
    }

    /**
     * Renders one extends, implements, or uses clause.
     *
     * Generic tags from the PHPDoc take precedence over the plain names so
     * the displayed parents carry their type arguments.
     *
     * @param list<string> $names
     * @param list<DocTag> $tags
     */
    public function parentClause(RenderKit $services, string $keyword, array $names, array $tags, TypeRenderContext $context): string
    {
        $parts = [];
        $covered = [];
        foreach ($tags as $tag) {
            if ($tag->type !== null) {
                $parts[] = $services->typeHtml->node($tag->type, $context);
                $covered[] = $tag->type;
            }
        }

        foreach ($names as $name) {
            $shown = false;
            foreach ($covered as $type) {
                $base = $type instanceof \PHPStan\PhpDocParser\Ast\Type\GenericTypeNode ? $type->type->name : ($type instanceof IdentifierTypeNode ? $type->name : null);
                if ($base !== null && strcasecmp($services->typeHtml->resolve($base, $context), $name) === 0) {
                    $shown = true;
                    break;
                }
            }

            if (!$shown) {
                $parts[] = $services->typeHtml->className($name, $context);
            }
        }

        if ($parts === []) {
            return '';
        }

        return "\n    " . '<span class="t-key">' . $keyword . '</span> ' . implode(', ', $parts);
    }

    /**
     * Renders the generic template parameter list of a declaration.
     *
     * @param list<TemplateDoc> $templates
     */
    public function templateList(RenderKit $services, array $templates, TypeRenderContext $context): string
    {
        if ($templates === []) {
            return '';
        }

        $parts = [];
        foreach ($templates as $template) {
            $part = '<span class="t-gen">' . $services->escaper->e($template->name) . '</span>';
            if ($template->bound !== null) {
                $part .= ' <span class="t-key">of</span> ' . $services->typeHtml->node($template->bound, $context);
            }

            $parts[] = $part;
        }

        return '&lt;' . implode(', ', $parts) . '&gt;';
    }

    /**
     * Renders one method signature.
     */
    public function methodSignature(RenderKit $services, MethodDoc $method, TypeRenderContext $context): string
    {
        $keywords = [];
        if ($method->isFinal) {
            $keywords[] = 'final';
        }

        if ($method->isAbstract) {
            $keywords[] = 'abstract';
        }

        $keywords[] = $method->visibility;
        if ($method->isStatic) {
            $keywords[] = 'static';
        }

        $keywords[] = 'function';
        $head = '<span class="t-key">' . implode('</span> <span class="t-key">', $keywords) . '</span> '
            . '<span class="sig-name">' . $services->escaper->e($method->name) . '</span>'
            . $this->templateList($services, $method->docBlock !== null ? $method->docBlock->templates : [], $context);
        $return = $services->typeHtml->render(
            $method->returnType->annotated !== null ? $method->returnType->annotated->type : null,
            $method->returnType->native,
            $context,
        );

        return $this->callableSignature($services, $head, $method->parameters, $return !== '' ? ': ' . $return : '', $context);
    }

    /**
     * Renders one function signature.
     */
    public function functionSignature(RenderKit $services, FunctionDoc $function, TypeRenderContext $context): string
    {
        $head = '<span class="t-key">function</span> <span class="sig-name">' . $services->escaper->e($function->shortName) . '</span>'
            . $this->templateList($services, $function->docBlock !== null ? $function->docBlock->templates : [], $context);
        $return = $services->typeHtml->render(
            $function->returnType->annotated !== null ? $function->returnType->annotated->type : null,
            $function->returnType->native,
            $context,
        );

        return $this->callableSignature($services, $head, $function->parameters, $return !== '' ? ': ' . $return : '', $context);
    }

    /**
     * Renders a callable head with its parameter list, wrapping when long.
     *
     * @param list<\PhpAiToolkit\DocGen\Analysis\Model\ParameterDoc> $parameters
     */
    public function callableSignature(RenderKit $services, string $head, array $parameters, string $returnSuffix, TypeRenderContext $context): string
    {
        $rendered = [];
        $plainLength = 0;
        foreach ($parameters as $parameter) {
            $html = $this->parameter($services, $parameter, $context);
            $rendered[] = $html;
            $plainLength += strlen(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) + 2;
        }

        $plainLength += strlen(html_entity_decode(strip_tags($head . $returnSuffix), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        if ($plainLength > self::WRAP_LENGTH && count($rendered) > 1) {
            return $head . "(\n    " . implode(",\n    ", $rendered) . ",\n)" . $returnSuffix;
        }

        return $head . '(' . implode(', ', $rendered) . ')' . $returnSuffix;
    }

    /**
     * Renders one parameter with its most precise type.
     */
    public function parameter(RenderKit $services, \PhpAiToolkit\DocGen\Analysis\Model\ParameterDoc $parameter, TypeRenderContext $context): string
    {
        $html = '';
        if ($parameter->promotedVisibility !== null) {
            $html .= '<span class="t-key">' . $parameter->promotedVisibility . '</span> ';
        }

        $type = $services->typeHtml->render(
            $parameter->type->annotated !== null ? $parameter->type->annotated->type : null,
            $parameter->type->native,
            $context,
        );
        if ($type !== '') {
            $html .= $type . ' ';
        }

        $html .= ($parameter->byRef ? '&amp;' : '') . ($parameter->variadic ? '...' : '')
            . '<span class="t-var">$' . $services->escaper->e($parameter->name) . '</span>';
        if ($parameter->defaultText !== null) {
            $html .= ' = <span class="t-lit">' . $services->escaper->e($parameter->defaultText) . '</span>';
        }

        return $html;
    }

    /**
     * Renders one property signature.
     */
    public function propertySignature(RenderKit $services, PropertyDoc $property, TypeRenderContext $context): string
    {
        $keywords = [$property->visibility];
        if ($property->isStatic) {
            $keywords[] = 'static';
        }

        $html = '<span class="t-key">' . implode('</span> <span class="t-key">', $keywords) . '</span> ';
        $type = $services->typeHtml->render(
            $property->type->annotated !== null ? $property->type->annotated->type : null,
            $property->type->native,
            $context,
        );
        if ($type !== '') {
            $html .= $type . ' ';
        }

        $html .= '<span class="t-var">$' . $services->escaper->e($property->name) . '</span>';
        if ($property->defaultText !== null) {
            $html .= ' = <span class="t-lit">' . $services->escaper->e($property->defaultText) . '</span>';
        }

        return $html;
    }

    /**
     * Renders one class constant signature.
     */
    public function constantSignature(RenderKit $services, ConstantDoc $constant, TypeRenderContext $context): string
    {
        $type = $constant->docBlock !== null && $constant->docBlock->var !== null && $constant->docBlock->var->type !== null
            ? $services->typeHtml->node($constant->docBlock->var->type, $context) . ' '
            : '';

        return '<span class="t-key">' . $constant->visibility . '</span> <span class="t-key">const</span> '
            . $type
            . '<span class="sig-name">' . $services->escaper->e($constant->name) . '</span>'
            . ($constant->valueText !== null ? ' = <span class="t-lit">' . $services->escaper->e($constant->valueText) . '</span>' : '');
    }

    /**
     * Renders one enum case signature.
     */
    public function caseSignature(RenderKit $services, EnumCaseDoc $case): string
    {
        return '<span class="t-key">case</span> <span class="sig-name">' . $services->escaper->e($case->name) . '</span>'
            . ($case->valueText !== null ? ' = <span class="t-lit">' . $services->escaper->e($case->valueText) . '</span>' : '');
    }
}
