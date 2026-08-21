<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Model;

/**
 * Structured view of one PHPDoc block.
 *
 * Types come from PHPStan or Psalm prefixed tags when present, falling back
 * to the standard tags, so the model always carries the most precise type.
 *
 * @property-read string $summary
 * @property-read string $description
 * @property-read array<string, DocTag> $params
 * @property-read ?DocTag $return
 * @property-read ?DocTag $var
 * @property-read list<DocTag> $throws
 * @property-read list<TemplateDoc> $templates
 * @property-read list<TypeAliasDoc> $aliases
 * @property-read list<DocTag> $extendsTags
 * @property-read list<DocTag> $implementsTags
 * @property-read list<DocTag> $usesTags
 * @property-read ?string $deprecated
 * @property-read bool $internal
 * @property-read list<string> $visibility
 * @property-read string $raw
 */
final class DocBlock
{
    /**
     * @param array<string, DocTag> $params
     * @param list<DocTag> $throws
     * @param list<TemplateDoc> $templates
     * @param list<TypeAliasDoc> $aliases
     * @param list<DocTag> $extendsTags
     * @param list<DocTag> $implementsTags
     * @param list<DocTag> $usesTags
     * @param list<string> $visibility scope values declared with the visibility tag
     */
    public function __construct(
        /** @readonly */
        private string $summary,
        /** @readonly */
        private string $description,
        /** @readonly */
        private array $params,
        /** @readonly */
        private ?DocTag $return,
        /** @readonly */
        private ?DocTag $var,
        /** @readonly */
        private array $throws,
        /** @readonly */
        private array $templates,
        /** @readonly */
        private array $aliases,
        /** @readonly */
        private array $extendsTags,
        /** @readonly */
        private array $implementsTags,
        /** @readonly */
        private array $usesTags,
        /** @readonly */
        private ?string $deprecated,
        /** @readonly */
        private bool $internal,
        /** @readonly */
        private string $raw,
        /**
         * @var list<string>
         * @readonly
         */
        private array $visibility = [],
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'summary' => $this->summary,
            'description' => $this->description,
            'params' => $this->params,
            'return' => $this->return,
            'var' => $this->var,
            'throws' => $this->throws,
            'templates' => $this->templates,
            'aliases' => $this->aliases,
            'extendsTags' => $this->extendsTags,
            'implementsTags' => $this->implementsTags,
            'usesTags' => $this->usesTags,
            'deprecated' => $this->deprecated,
            'internal' => $this->internal,
            'raw' => $this->raw,
            'visibility' => $this->visibility,
            default => null,
        };
    }
}
