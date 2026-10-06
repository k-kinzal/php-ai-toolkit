<?php

declare(strict_types=1);

namespace Guard\Structure\Php;

/**
 * Metrics calculated independently of thresholds or profile assignments.
 *
 * @property-read FileMetric\FileMetric $file
 * @property-read list<ClassLikeMetric\ClassLikeMetric> $classes
 * @property-read list<FunctionMetric\FunctionMetric> $functions
 * @property-read bool $readable
 */
final class SourceMetrics implements \Guard\Structure\Subject
{
    /**
     * @param FileMetric\FileMetric $file
     * @param list<ClassLikeMetric\ClassLikeMetric> $classes
     * @param list<FunctionMetric\FunctionMetric> $functions
     * @param bool $readable
     */
    public function __construct(
        /** @readonly */
        private FileMetric\FileMetric $file,
        /** @readonly */
        private array $classes,
        /** @readonly */
        private array $functions,
        /** @readonly */
        private bool $readable,
    ) {
    }

    /**
     * Returns a collected value.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'file' => $this->file,
            'classes' => $this->classes,
            'functions' => $this->functions,
            'readable' => $this->readable,
            default => null,
        };
    }
}
