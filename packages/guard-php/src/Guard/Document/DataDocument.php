<?php

declare(strict_types=1);

namespace Toolkit\Guard\Document;

use JsonException;
use Nette\Neon\Neon;
use Symfony\Component\Yaml\Yaml;
use Toolkit\Guard\Policy\PolicyException;
use Yosymfony\Toml\Toml;

/**
 * Parses and edits JSON, YAML, NEON and TOML configuration documents.
 */
final class DataDocument
{
    /**
     * Creates a parsed document for a supported data format.
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function __construct(private string $format, private string $source)
    {
        $this->decode($source);
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
            'yaml', 'yml' => Yaml::parse($source, Yaml::PARSE_OBJECT_FOR_MAP | Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE),
            'neon' => Neon::decode($source),
            'toml' => Toml::parse($source, true),
            default => throw new PolicyException('Unsupported document format: ' . $this->format),
        };
    }
    /**
     * Reads a field without confusing a missing value with null.
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function read(string $selector): Selection
    {
        return (new Pointer())->read($this->decode($this->source), $selector);
    }
    /**
     * @param mixed $value
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function write(string $selector, $value): void
    {
        $this->source = $this->encodeData((new Pointer())->write($this->decode($this->source), $selector, $value));
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
        if (!(new \Toolkit\Guard\Policy\Constraint())->equal($data, $this->decode($encoded))) {
            throw new PolicyException('Encoding ' . $this->format . ' would change unrelated data types. The file was not written.');
        }
        return $encoded;
    }
}
