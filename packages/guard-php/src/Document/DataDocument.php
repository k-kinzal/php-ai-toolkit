<?php

declare(strict_types=1);

namespace Guard\Document;

use Guard\Policy\PolicyException;
use JsonException;
use Nette\Neon\Neon;
use Symfony\Component\Yaml\Yaml;
use Yosymfony\Toml\Toml;

/**
 * Parses and edits JSON, JSON5, YAML, NEON and TOML configuration documents.
 */
final class DataDocument
{
    private DocumentNode $data;
    /**
     * Creates a parsed document for a supported data format.
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function __construct(private string $format, private string $source)
    {
        $this->data = new DocumentNode($this->decode($source));
    }
    /**
     * @return mixed
     * @throws PolicyException when the requested format is unsupported
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function decode(string $source): mixed
    {
        return match ($this->format) {
            'json' => json_decode($source, false, 512, JSON_THROW_ON_ERROR),
            'json5' => (new Json5Reader())->decode($source),
            'yaml', 'yml' => Yaml::parse($source, Yaml::PARSE_OBJECT_FOR_MAP | Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE),
            'neon' => Neon::decode($source),
            'toml' => Toml::parse($source, true),
            default => throw new PolicyException('Unsupported document format: ' . $this->format),
        };
    }
    /**
     * Reads a collected field without confusing a missing value with null.
     * @throws JsonException
     */
    public function read(string $selector): Selection
    {
        return (new Pointer())->read($this->data->native(), $selector);
    }
    /**
     * @param mixed $value
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function write(string $selector, $value): void
    {
        $source = $this->encodeData((new Pointer())->write($this->data->native(), $selector, $value));
        $this->data = new DocumentNode($this->decode($source));
        $this->source = $source;
    }
    /**
     * Encodes a changed document and verifies that it can be parsed again.
     */
    public function encode(): string
    {
        return $this->source;
    }
    /**
     * @param mixed $data
     * @throws PolicyException when serialization cannot preserve the data
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function encodeData($data): string
    {
        $encoded = match ($this->format) {
            'json' => json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION) . "\n",
            'yaml', 'yml' => Yaml::dump($data, 20, 2, Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE),
            'neon' => Neon::encode($data, true),
            'toml' => (new TomlEncoder())->encode($data),
            default => throw new PolicyException('Unsupported document format: ' . $this->format),
        };
        if (!(new \Guard\Policy\Constraint())->equal($data, $this->decode($encoded))) {
            throw new PolicyException('Encoding ' . $this->format . ' would change unrelated data types. The file was not written.');
        }
        return $encoded;
    }
}
