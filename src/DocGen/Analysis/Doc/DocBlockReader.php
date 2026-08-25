<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Doc;

use function array_values;
use function implode;

use PHPStan\PhpDocParser\Ast\PhpDoc\DeprecatedTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ImplementsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ThrowsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasImportTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\UsesTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;

use function preg_split;

use Toolkit\DocGen\Analysis\Model\DocBlock;
use Toolkit\DocGen\Analysis\Model\DocTag;
use Toolkit\DocGen\Analysis\Model\TemplateDoc;
use Toolkit\DocGen\Analysis\Model\TypeAliasDoc;

use function trim;

/**
 * Reads PHPDoc comments into the structured DocBlock model.
 *
 * PHPStan prefixed tags take precedence over Psalm prefixed tags, which take
 * precedence over the standard tags, so the model carries the most precise
 * declared type for every element.
 */
final class DocBlockReader
{
    /** @readonly */
    private PhpDocParserBridge $bridge;

    /**
     * Creates a doc block reader from the version bridge.
     */
    public function __construct(?PhpDocParserBridge $bridge = null)
    {
        $this->bridge = $bridge ?? new PhpDocParserBridge();
    }

    /**
     * Reads one PHPDoc comment, or null when there is none.
     */
    public function read(?string $docComment): ?DocBlock
    {
        if ($docComment === null || trim($docComment) === '') {
            return null;
        }

        $node = $this->bridge->parse($docComment);
        [$summary, $description] = $this->text($node);

        return new DocBlock(
            $summary,
            $description,
            $this->params($node),
            $this->returnTag($node),
            $this->varTag($node),
            $this->throwsTags($node),
            $this->templates($node),
            $this->aliases($node),
            $this->relationTags($node, ['@extends', '@phpstan-extends', '@template-extends']),
            $this->relationTags($node, ['@implements', '@phpstan-implements', '@template-implements']),
            $this->relationTags($node, ['@use', '@phpstan-use', '@template-use']),
            $this->deprecated($node),
            $node->getTagsByName('@internal') !== [],
            $docComment,
            $this->visibility($node),
        );
    }

    /**
     * Reads the namespace visibility scopes declared on the element.
     *
     * The scopes are kept verbatim. Resolving what a scope covers belongs to
     * scope-guard, which enforces them; documentation only states what was declared.
     *
     * @return list<string>
     */
    public function visibility(PhpDocNode $node): array
    {
        $scopes = [];
        foreach ($node->getTagsByName('@visibility') as $tag) {
            $value = trim((string) $tag->value);
            if ($value !== '') {
                $scopes[] = $value;
            }
        }

        return $scopes;
    }

    /**
     * Splits the leading free text into summary and description.
     *
     * @return array{string, string}
     */
    public function text(PhpDocNode $node): array
    {
        $lines = [];
        foreach ($node->children as $child) {
            if ($child instanceof PhpDocTagNode) {
                break;
            }

            if ($child instanceof PhpDocTextNode) {
                $lines[] = $child->text;
            }
        }

        $text = trim(implode("\n", $lines));
        if ($text === '') {
            return ['', ''];
        }

        $paragraphs = preg_split("/\n\\s*\n/", $text, 2);
        if ($paragraphs === false) {
            $paragraphs = [$text];
        }

        return [trim($paragraphs[0]), isset($paragraphs[1]) ? trim($paragraphs[1]) : ''];
    }

    /**
     * Collects the parameter tags keyed by parameter name.
     *
     * @return array<string, DocTag>
     */
    public function params(PhpDocNode $node): array
    {
        $tags = [];
        foreach (['@param', '@psalm-param', '@phpstan-param'] as $name) {
            foreach ($node->getTagsByName($name) as $tag) {
                if ($tag->value instanceof ParamTagValueNode) {
                    $tags[$tag->value->parameterName] = new DocTag($tag->value->type, $tag->value->description);
                }
            }
        }

        return $tags;
    }

    /**
     * Returns the most precise return tag, or null when there is none.
     */
    public function returnTag(PhpDocNode $node): ?DocTag
    {
        $result = null;
        foreach (['@return', '@psalm-return', '@phpstan-return'] as $name) {
            foreach ($node->getTagsByName($name) as $tag) {
                if ($tag->value instanceof ReturnTagValueNode) {
                    $result = new DocTag($tag->value->type, $tag->value->description);
                }
            }
        }

        return $result;
    }

    /**
     * Returns the most precise var tag, or null when there is none.
     */
    public function varTag(PhpDocNode $node): ?DocTag
    {
        $result = null;
        foreach (['@var', '@psalm-var', '@phpstan-var'] as $name) {
            foreach ($node->getTagsByName($name) as $tag) {
                if ($tag->value instanceof VarTagValueNode) {
                    $result = new DocTag($tag->value->type, $tag->value->description);
                }
            }
        }

        return $result;
    }

    /**
     * Collects all throws tags.
     *
     * @return list<DocTag>
     */
    public function throwsTags(PhpDocNode $node): array
    {
        $tags = [];
        foreach ($node->getTagsByName('@throws') as $tag) {
            if ($tag->value instanceof ThrowsTagValueNode) {
                $tags[] = new DocTag($tag->value->type, $tag->value->description);
            }
        }

        return $tags;
    }

    /**
     * Collects the generic template declarations, first declaration wins.
     *
     * @return list<TemplateDoc>
     */
    public function templates(PhpDocNode $node): array
    {
        $names = [
            '@template', '@template-covariant', '@template-contravariant',
            '@phpstan-template', '@phpstan-template-covariant', '@phpstan-template-contravariant',
            '@psalm-template', '@psalm-template-covariant', '@psalm-template-contravariant',
        ];
        $templates = [];
        foreach ($names as $name) {
            foreach ($node->getTagsByName($name) as $tag) {
                if ($tag->value instanceof TemplateTagValueNode && !isset($templates[$tag->value->name])) {
                    $templates[$tag->value->name] = new TemplateDoc($tag->value->name, $tag->value->bound, $tag->value->description);
                }
            }
        }

        return array_values($templates);
    }

    /**
     * Collects local and imported type alias declarations.
     *
     * @return list<TypeAliasDoc>
     */
    public function aliases(PhpDocNode $node): array
    {
        $aliases = [];
        foreach (['@phpstan-type', '@psalm-type'] as $name) {
            foreach ($node->getTagsByName($name) as $tag) {
                if ($tag->value instanceof TypeAliasTagValueNode) {
                    $aliases[] = new TypeAliasDoc($tag->value->alias, $tag->value->type, null);
                }
            }
        }

        foreach (['@phpstan-import-type', '@psalm-import-type'] as $name) {
            foreach ($node->getTagsByName($name) as $tag) {
                if ($tag->value instanceof TypeAliasImportTagValueNode) {
                    $aliases[] = new TypeAliasDoc(
                        $tag->value->importedAs ?? $tag->value->importedAlias,
                        null,
                        $tag->value->importedFrom->name,
                    );
                }
            }
        }

        return $aliases;
    }

    /**
     * Collects generic parent relation tags such as extends and implements.
     *
     * @param list<string> $names
     *
     * @return list<DocTag>
     */
    public function relationTags(PhpDocNode $node, array $names): array
    {
        $tags = [];
        foreach ($names as $name) {
            foreach ($node->getTagsByName($name) as $tag) {
                $value = $tag->value;
                if ($value instanceof ExtendsTagValueNode || $value instanceof ImplementsTagValueNode || $value instanceof UsesTagValueNode) {
                    $tags[] = new DocTag($value->type, $value->description);
                }
            }
        }

        return $tags;
    }

    /**
     * Returns the deprecation note, or null when the element is not deprecated.
     */
    public function deprecated(PhpDocNode $node): ?string
    {
        foreach ($node->getTagsByName('@deprecated') as $tag) {
            if ($tag->value instanceof DeprecatedTagValueNode) {
                return $tag->value->description;
            }

            return '';
        }

        return null;
    }
}
