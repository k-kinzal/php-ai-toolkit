<?php

declare(strict_types=1);

namespace Guard\Collect\Php;

use Guard\Collect\Php\ClassLikeMetric\ClassLikeMetricCollector;
use Guard\Collect\Php\FileMetric\FileMetric;
use Guard\Collect\Php\FunctionMetric\FunctionMetricCollector;
use Guard\Collect\Php\Token\TokenLineCounter;
use PhpToken;

/**
 * Reads PHP source and measures it without selecting or applying any policy.
 */
final class SourceMetricReader
{
    /**
     * Reads one PHP file; unreadable source retains the existing empty-metrics behavior.
     */
    public function read(string $path, string $relativePath): SourceMetrics
    {
        $source = file_get_contents($path);
        if ($source === false) {
            return new SourceMetrics(new FileMetric($relativePath, 0, 0), [], [], false);
        }
        return $this->parse($source, $relativePath);
    }
    /**
     * Structures already-read source so additional policies can reuse the same metrics.
     */
    public function parse(string $source, string $relativePath): SourceMetrics
    {
        $tokens = array_values(PhpToken::tokenize($source));
        $counter = new TokenLineCounter();
        return new SourceMetrics(
            new FileMetric($relativePath, $counter->physicalLines($source), $counter->nonCommentLines($tokens)),
            (new ClassLikeMetricCollector())->collect($tokens),
            (new FunctionMetricCollector())->collect($tokens),
            true,
        );
    }
}
