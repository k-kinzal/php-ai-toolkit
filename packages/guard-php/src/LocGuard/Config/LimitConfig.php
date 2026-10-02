<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Config;

/**
 * Effective optional thresholds used by one LocGuard policy.
 *
 * @property-read ?int $maxFileLines
 * @property-read ?int $maxFileNcloc
 * @property-read ?int $maxClassLines
 * @property-read ?int $maxTraitLines
 * @property-read ?int $maxInterfaceLines
 * @property-read ?int $maxEnumLines
 * @property-read ?int $maxFunctionLines
 * @property-read ?int $maxMethodLines
 * @property-read ?int $maxFunctionCyclomaticComplexity
 * @property-read ?int $maxMethodCyclomaticComplexity
 */
final class LimitConfig
{
    /**
     * Creates effective line-count and complexity thresholds.
     */
    public function __construct(
        /** @readonly */
        private ?int $maxFileLines,
        /** @readonly */
        private ?int $maxFileNcloc,
        /** @readonly */
        private ?int $maxClassLines,
        /** @readonly */
        private ?int $maxTraitLines,
        /** @readonly */
        private ?int $maxInterfaceLines,
        /** @readonly */
        private ?int $maxEnumLines,
        /** @readonly */
        private ?int $maxFunctionLines,
        /** @readonly */
        private ?int $maxMethodLines,
        /** @readonly */
        private ?int $maxFunctionCyclomaticComplexity,
        /** @readonly */
        private ?int $maxMethodCyclomaticComplexity,
    ) {
    }

    /**
     * Creates a policy with every metric disabled.
     */
    public static function disabled(): self
    {
        return new self(null, null, null, null, null, null, null, null, null, null);
    }

    /**
     * Creates effective limits from their canonical metric paths.
     *
     * @param array<string, ?int> $values
     */
    public static function fromValues(array $values): self
    {
        return new self(
            $values['file.lines'] ?? null,
            $values['file.ncloc'] ?? null,
            $values['class.lines'] ?? null,
            $values['trait.lines'] ?? null,
            $values['interface.lines'] ?? null,
            $values['enum.lines'] ?? null,
            $values['function.lines'] ?? null,
            $values['method.lines'] ?? null,
            $values['function.cyclomatic_complexity'] ?? null,
            $values['method.cyclomatic_complexity'] ?? null,
        );
    }

    /**
     * Returns effective values keyed by canonical metric path.
     *
     * @return array<string, ?int>
     */
    public function values(): array
    {
        return [
            'file.lines' => $this->maxFileLines,
            'file.ncloc' => $this->maxFileNcloc,
            'class.lines' => $this->maxClassLines,
            'trait.lines' => $this->maxTraitLines,
            'interface.lines' => $this->maxInterfaceLines,
            'enum.lines' => $this->maxEnumLines,
            'function.lines' => $this->maxFunctionLines,
            'method.lines' => $this->maxMethodLines,
            'function.cyclomatic_complexity' => $this->maxFunctionCyclomaticComplexity,
            'method.cyclomatic_complexity' => $this->maxMethodCyclomaticComplexity,
        ];
    }

    /**
     * Reports whether at least one metric is enabled.
     */
    public function hasEnabledLimit(): bool
    {
        foreach ($this->values() as $value) {
            if ($value !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'maxFileLines' => $this->maxFileLines,
            'maxFileNcloc' => $this->maxFileNcloc,
            'maxClassLines' => $this->maxClassLines,
            'maxTraitLines' => $this->maxTraitLines,
            'maxInterfaceLines' => $this->maxInterfaceLines,
            'maxEnumLines' => $this->maxEnumLines,
            'maxFunctionLines' => $this->maxFunctionLines,
            'maxMethodLines' => $this->maxMethodLines,
            'maxFunctionCyclomaticComplexity' => $this->maxFunctionCyclomaticComplexity,
            'maxMethodCyclomaticComplexity' => $this->maxMethodCyclomaticComplexity,
            default => null,
        };
    }
}
