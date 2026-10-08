<?php

declare(strict_types=1);

namespace Guard\Policy;

use JsonException;

/**
 * A required constraint or a non-fatal recommendation for a document field.
 *
 * @property-read string $id
 * @property-read string $file
 * @property-read string $format
 * @property-read string $select
 * @property-read string $level
 * @property-read array<string, mixed> $assertions
 * @property-read mixed $repair
 * @property-read string $message
 * @property-read bool $repairable
 */
final class Rule
{
    /** @readonly */
    private string $assertionsJson;

    /** @readonly */
    private string $repairJson;

    /**
     * Creates the immutable value.
     * @param string $id
     * @param string $file
     * @param string $format
     * @param string $select
     * @param string $level
     * @param array<string, mixed> $assertions
     * @param mixed $repair
     * @param bool $repairable
     * @throws JsonException
     */
    public function __construct(
        /** @readonly */
        private string $id,
        /** @readonly */
        private string $file,
        /** @readonly */
        private string $format,
        /** @readonly */
        private string $select,
        /** @readonly */
        private string $level,
        array $assertions,
        mixed $repair,
        /** @readonly */
        private bool $repairable,
        /** @readonly */
        private string $message = '',
    ) {
        $this->repairJson = json_encode($repair, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        $this->assertionsJson = json_encode($assertions, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    }

    /** Returns a declared immutable property.
     * @return mixed
     * @throws JsonException
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'id' => $this->id,
            'file' => $this->file,
            'format' => $this->format,
            'select' => $this->select,
            'level' => $this->level,
            'assertions' => json_decode($this->assertionsJson, true, 512, JSON_THROW_ON_ERROR),
            'repair' => json_decode($this->repairJson, false, 512, JSON_THROW_ON_ERROR),
            'repairable' => $this->repairable,
            'message' => $this->message,
            default => null,
        };
    }
}
