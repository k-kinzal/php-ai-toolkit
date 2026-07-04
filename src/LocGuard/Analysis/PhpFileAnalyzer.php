<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function array_merge;
use function array_values;
use function file_get_contents;

use PhpAiToolkit\LocGuard\Config\LimitConfig;
use PhpToken;

use function sprintf;

/**
 * Calculates LocGuard metrics and violations for one PHP source file.
 */
final class PhpFileAnalyzer
{
    /**
     * Creates a PHP file analyzer from metric collectors.
     */
    public function __construct(
        private readonly TokenLineCounter $lineCounter = new TokenLineCounter(),
        private readonly FunctionMetricCollector $functionCollector = new FunctionMetricCollector(),
        private readonly ClassLikeMetricCollector $classLikeCollector = new ClassLikeMetricCollector(),
    ) {
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

        $tokens = array_values(PhpToken::tokenize($source, TOKEN_PARSE));
        $file = new FileMetric(
            $relativePath,
            $this->lineCounter->physicalLines($source),
            $this->lineCounter->nonCommentLines($tokens),
        );

        $violations = array_merge(
            $this->fileViolations($file, $limits),
            $this->classLikeViolations($relativePath, $this->classLikeCollector->collect($tokens), $limits),
            $this->functionViolations($relativePath, $this->functionCollector->collect($tokens), $limits),
        );

        return new FileAnalysis($file, $violations);
    }

    /**
     * @return list<Violation>
     */
    private function fileViolations(FileMetric $file, LimitConfig $limits): array
    {
        $violations = [];
        if ($file->physicalLines > $limits->maxFileLines) {
            $violations[] = new Violation(
                $file->path,
                1,
                'file_lines',
                $file->physicalLines,
                $limits->maxFileLines,
                sprintf('File has %d physical lines; maximum is %d.', $file->physicalLines, $limits->maxFileLines),
            );
        }

        if ($file->nonCommentLines > $limits->maxFileNcloc) {
            $violations[] = new Violation(
                $file->path,
                1,
                'file_ncloc',
                $file->nonCommentLines,
                $limits->maxFileNcloc,
                sprintf('File has %d non-comment lines of code; maximum is %d.', $file->nonCommentLines, $limits->maxFileNcloc),
            );
        }

        return $violations;
    }

    /**
     * @param list<ClassLikeMetric> $metrics
     * @return list<Violation>
     */
    private function classLikeViolations(string $relativePath, array $metrics, LimitConfig $limits): array
    {
        $violations = [];
        foreach ($metrics as $metric) {
            $limit = $this->classLikeLimit($metric, $limits);
            if ($metric->lineCount() <= $limit) {
                continue;
            }

            $violations[] = new Violation(
                $relativePath,
                $metric->startLine,
                $metric->kind . '_lines',
                $metric->lineCount(),
                $limit,
                sprintf('%s %s has %d physical lines; maximum is %d.', $metric->kind, $metric->name, $metric->lineCount(), $limit),
            );
        }

        return $violations;
    }

    private function classLikeLimit(ClassLikeMetric $metric, LimitConfig $limits): int
    {
        if ($metric->kind === 'trait') {
            return $limits->maxTraitLines;
        }

        if ($metric->kind === 'interface') {
            return $limits->maxInterfaceLines;
        }

        if ($metric->kind === 'enum') {
            return $limits->maxEnumLines;
        }

        return $limits->maxClassLines;
    }

    /**
     * @param list<FunctionMetric> $metrics
     * @return list<Violation>
     */
    private function functionViolations(string $relativePath, array $metrics, LimitConfig $limits): array
    {
        $violations = [];
        foreach ($metrics as $metric) {
            $violations = array_merge($violations, $this->functionLineViolations($relativePath, $metric, $limits));
            if ($metric->cyclomaticComplexity > $limits->maxCyclomaticComplexity) {
                $violations[] = $this->complexityViolation($relativePath, $metric, $limits);
            }
        }

        return $violations;
    }

    /**
     * @return list<Violation>
     */
    private function functionLineViolations(string $relativePath, FunctionMetric $metric, LimitConfig $limits): array
    {
        $limit = $metric->kind === 'method' ? $limits->maxMethodLines : $limits->maxFunctionLines;
        if ($metric->lineCount() <= $limit) {
            return [];
        }

        return [
            new Violation(
                $relativePath,
                $metric->startLine,
                $metric->kind . '_lines',
                $metric->lineCount(),
                $limit,
                sprintf('%s %s has %d physical lines; maximum is %d.', $metric->kind, $metric->name, $metric->lineCount(), $limit),
            ),
        ];
    }

    private function complexityViolation(string $relativePath, FunctionMetric $metric, LimitConfig $limits): Violation
    {
        return new Violation(
            $relativePath,
            $metric->startLine,
            'cyclomatic_complexity',
            $metric->cyclomaticComplexity,
            $limits->maxCyclomaticComplexity,
            sprintf(
                '%s %s has cyclomatic complexity %d; maximum is %d.',
                $metric->kind,
                $metric->name,
                $metric->cyclomaticComplexity,
                $limits->maxCyclomaticComplexity,
            ),
        );
    }
}
