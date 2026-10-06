<?php

declare(strict_types=1);

namespace Guard\Collect\Configuration;

/**
 * A parsed configuration file with its original bytes and applicable field rules.
 *
 * @property-read string $path
 * @property-read string $source
 * @property-read string $format
 * @property-read \Guard\Document\DataDocument|\Guard\Document\XmlDocument|\Guard\Document\PhpDocument $document
 * @property-read list<\Guard\Policy\Rule> $rules
 */
final class ConfigurationDocument implements \Guard\Collect\Subject
{
    /**
     * @param string $path
     * @param string $source
     * @param string $format
     * @param \Guard\Document\DataDocument|\Guard\Document\XmlDocument|\Guard\Document\PhpDocument $document
     * @param list<\Guard\Policy\Rule> $rules
     */
    public function __construct(
        /** @readonly */
        private string $path,
        /** @readonly */
        private string $source,
        /** @readonly */
        private string $format,
        /** @readonly */
        private \Guard\Document\DataDocument|\Guard\Document\XmlDocument|\Guard\Document\PhpDocument $document,
        /** @readonly */
        private array $rules,
    ) {
    }

    /**
     * Returns a collected value.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'path' => $this->path,
            'source' => $this->source,
            'format' => $this->format,
            'document' => $this->document,
            'rules' => $this->rules,
            default => null,
        };
    }
}
