<?php

declare(strict_types=1);

namespace Guard\Collect;

use JsonException;
use RuntimeException;

/**
 * Structured file input, including missing and unreadable files.
 *
 * @property-read FileRecord $file
 * @property-read bool $readable
 * @property-read ?\Guard\Structure\Subject $data
 * @property-read RuntimeException|JsonException|\Nette\Neon\Exception|null $failure
 */
final class StructuredFile
{
    /**
     * Creates the immutable value.
     * @param FileRecord $file
     * @param bool $readable
     * @param ?\Guard\Structure\Subject $data
     * @param RuntimeException|JsonException|\Nette\Neon\Exception|null $failure
     */
    public function __construct(
        /** @readonly */
        private FileRecord $file,
        /** @readonly */
        private bool $readable,
        /** @readonly */
        private ?\Guard\Structure\Subject $data,
        /** @readonly */
        private RuntimeException|JsonException|\Nette\Neon\Exception|null $failure,
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
            default => null,
        };
    }

    /** Returns the requested structure or its deferred parsing failure.
     * @throws RuntimeException
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function value(): ?\Guard\Structure\Subject
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }
        return $this->data;
    }
}
