<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function array_merge;
use function array_values;
use function file_get_contents;

use PhpAiToolkit\LocGuard\Config\LimitConfig;
use PhpToken;

/**
 * Calculates LocGuard metrics and violations for one PHP source file.
 */
final class PhpFileAnalyzer
{
    /** @readonly */
    private TokenLineCounter $lineCounter;

    /** @readonly */
    private FunctionMetricCollector $functionCollector;

    /** @readonly */
    private ClassLikeMetricCollector $classLikeCollector;

    /** @readonly */
    private FileMetricViolationBuilder $fileViolationBuilder;

    /** @readonly */
    private ClassLikeMetricViolationBuilder $classLikeViolationBuilder;

    /** @readonly */
    private FunctionMetricViolationBuilder $functionViolationBuilder;

    /**
     * Creates a PHP file analyzer from metric collectors.
     */
    public function __construct(
        ?TokenLineCounter $lineCounter = null,
        ?FunctionMetricCollector $functionCollector = null,
        ?ClassLikeMetricCollector $classLikeCollector = null,
        ?FileMetricViolationBuilder $fileViolationBuilder = null,
        ?ClassLikeMetricViolationBuilder $classLikeViolationBuilder = null,
        ?FunctionMetricViolationBuilder $functionViolationBuilder = null,
    ) {
        $this->lineCounter = $lineCounter ?? new TokenLineCounter();
        $this->functionCollector = $functionCollector ?? new FunctionMetricCollector();
        $this->classLikeCollector = $classLikeCollector ?? new ClassLikeMetricCollector();
        $this->fileViolationBuilder = $fileViolationBuilder ?? new FileMetricViolationBuilder();
        $this->classLikeViolationBuilder = $classLikeViolationBuilder ?? new ClassLikeMetricViolationBuilder();
        $this->functionViolationBuilder = $functionViolationBuilder ?? new FunctionMetricViolationBuilder();
    }

    /**
     * Analyzes one file and returns its metrics and threshold violations.
     */
    public function analyze(string $path, string $relativePath, LimitConfig $limits): FileAnalysis
    {
        $source = file_get_contents($path);
        if ($source === false) {
            return new FileAnalysis(new FileMetric($relativePath, 0, 0), []);
        }

        $tokens = array_values(PhpToken::tokenize($source));
        $file = new FileMetric(
            $relativePath,
            $this->lineCounter->physicalLines($source),
            $this->lineCounter->nonCommentLines($tokens),
        );

        $violations = array_merge(
            $this->fileViolationBuilder->violations($file, $limits),
            $this->classLikeViolationBuilder->violations($relativePath, $this->classLikeCollector->collect($tokens), $limits),
            $this->functionViolationBuilder->violations($relativePath, $this->functionCollector->collect($tokens), $limits),
        );

        return new FileAnalysis($file, $violations);
    }
}
