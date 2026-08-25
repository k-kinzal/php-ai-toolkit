<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Diff;

use function implode;
use function preg_replace;

use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\ConstantDoc;
use Toolkit\DocGen\Analysis\Model\DocBlock;
use Toolkit\DocGen\Analysis\Model\EnumCaseDoc;
use Toolkit\DocGen\Analysis\Model\FunctionDoc;
use Toolkit\DocGen\Analysis\Model\MethodDoc;
use Toolkit\DocGen\Analysis\Model\ParameterDoc;
use Toolkit\DocGen\Analysis\Model\PropertyDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;

use function trim;

/**
 * Reduces a documented element to the text that describes it.
 *
 * Two elements are the same when their fingerprints match, so what counts
 * as a change is defined here: everything the site shows, including the
 * PHPDoc prose, and nothing it does not, such as line numbers.
 */
final class SymbolFingerprint
{
    /**
     * Fingerprints the declaration head of one class-like symbol.
     */
    public function classHeader(ClassLikeDoc $classLike): string
    {
        return implode('|', [
            $classLike->kind,
            $classLike->shortName,
            $classLike->isAbstract ? 'abstract' : '',
            $classLike->isFinal ? 'final' : '',
            implode(',', $classLike->extends),
            implode(',', $classLike->implements),
            implode(',', $classLike->traits),
            $classLike->backingType ?? '',
            $this->docBlock($classLike->docBlock),
        ]);
    }

    /**
     * Fingerprints one method declaration.
     */
    public function method(MethodDoc $method): string
    {
        return implode('|', [
            $method->visibility,
            $method->isStatic ? 'static' : '',
            $method->isAbstract ? 'abstract' : '',
            $method->isFinal ? 'final' : '',
            $method->name,
            $this->parameters($method->parameters),
            $this->type($method->returnType),
            $this->docBlock($method->docBlock),
        ]);
    }

    /**
     * Fingerprints one top-level function declaration.
     */
    public function functionSymbol(FunctionDoc $function): string
    {
        return implode('|', [
            $function->shortName,
            $this->parameters($function->parameters),
            $this->type($function->returnType),
            $this->docBlock($function->docBlock),
        ]);
    }

    /**
     * Fingerprints one property declaration.
     */
    public function property(PropertyDoc $property): string
    {
        return implode('|', [
            $property->visibility,
            $property->isStatic ? 'static' : '',
            $property->isPromoted ? 'promoted' : '',
            $property->name,
            $this->type($property->type),
            $property->defaultText ?? '',
            $this->docBlock($property->docBlock),
        ]);
    }

    /**
     * Fingerprints one class constant declaration.
     */
    public function constant(ConstantDoc $constant): string
    {
        return implode('|', [
            $constant->visibility,
            $constant->name,
            $constant->valueText ?? '',
            $this->docBlock($constant->docBlock),
        ]);
    }

    /**
     * Fingerprints one enum case declaration.
     */
    public function enumCase(EnumCaseDoc $case): string
    {
        return implode('|', [$case->name, $case->valueText ?? '', $this->docBlock($case->docBlock)]);
    }

    /**
     * Fingerprints one parameter, its documented description included.
     */
    public function parameter(ParameterDoc $parameter): string
    {
        return implode('|', [
            $parameter->promotedVisibility ?? '',
            $this->type($parameter->type),
            $parameter->byRef ? 'ref' : '',
            $parameter->variadic ? 'variadic' : '',
            $parameter->name,
            $parameter->defaultText ?? '',
            $parameter->description,
        ]);
    }

    /**
     * Fingerprints a whole parameter list.
     *
     * @param list<ParameterDoc> $parameters
     */
    public function parameters(array $parameters): string
    {
        $parts = [];
        foreach ($parameters as $parameter) {
            $parts[] = $this->parameter($parameter);
        }

        return implode(';', $parts);
    }

    /**
     * Fingerprints one declared type with its documented refinement.
     */
    public function type(TypeSignature $type): string
    {
        $annotated = $type->annotated;
        if ($annotated === null) {
            return $type->native ?? '';
        }

        return ($type->native ?? '') . ' ' . ($annotated->type !== null ? (string) $annotated->type : '') . ' ' . $annotated->description;
    }

    /**
     * Fingerprints the throws tags of one declaration.
     */
    public function throwsTags(?DocBlock $docBlock): string
    {
        if ($docBlock === null) {
            return '';
        }

        $parts = [];
        foreach ($docBlock->throws as $tag) {
            $parts[] = ($tag->type !== null ? (string) $tag->type : '') . ' ' . $tag->description;
        }

        return implode(';', $parts);
    }

    /**
     * Fingerprints one PHPDoc block by the words it is made of.
     *
     * The comment markers and the line breaks are dropped first: reflowing
     * a comment leaves the rendered documentation exactly as it was, and a
     * documentation diff that reported it would report noise.
     */
    public function docBlock(?DocBlock $docBlock): string
    {
        if ($docBlock === null) {
            return '';
        }

        $text = preg_replace('#^\s*/\*+#', '', trim($docBlock->raw)) ?? $docBlock->raw;
        $text = preg_replace('#\*+/\s*$#', '', $text) ?? $text;
        $text = preg_replace('#^[ \t]*\*+[ \t]?#m', '', $text) ?? $text;

        return trim(preg_replace('/\s+/', ' ', $text) ?? $text);
    }
}
