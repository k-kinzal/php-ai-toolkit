<?php

declare(strict_types=1);

namespace Guard\Input;

use Guard\Structure\Subject;
use JsonException;
use Nette\Neon\Exception;
use RuntimeException;

/**
 * Structured file input, including missing and unreadable files.
 *
 * @property-read FileRecord $file
 * @property-read bool $readable
 * @property-read ?Subject $data
 * @property-read RuntimeException|JsonException|Exception|null $failure
 * @property-read array<string, FileRecord> $references
 */
final class StructuredFile
{
    /**
     * Creates the immutable value.
     * @param FileRecord $file
     * @param bool $readable
     * @param ?Subject $data
     * @param RuntimeException|JsonException|Exception|null $failure
     * @param array<string, FileRecord> $references
     */
    public function __construct(
        /** @readonly */
        private FileRecord $file,
        /** @readonly */
        private bool $readable,
        /** @readonly */
        private ?Subject $data,
        /** @readonly */
        private RuntimeException|JsonException|Exception|null $failure,
        /** @readonly */
        private array $references = [],
    ) {
    }

    /**
     * Returns a declared property.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'file' => $this->file,
            'readable' => $this->readable,
            'data' => $this->data,
            'failure' => $this->failure,
            'references' => $this->references,
            default => null,
        };
    }

    /** Returns the requested structure or its deferred parsing failure.
     * @throws RuntimeException
     * @throws JsonException
     * @throws Exception
     */
    public function value(): ?Subject
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }
        return $this->data;
    }
}
