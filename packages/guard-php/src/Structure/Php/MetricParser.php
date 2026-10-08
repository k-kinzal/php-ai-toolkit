<?php

declare(strict_types=1);

namespace Guard\Structure\Php;

use Guard\Structure\Php\ClassLikeMetric\ClassLikeMetricParser;
use Guard\Structure\Php\FileMetric\FileMetric;
use Guard\Structure\Php\FunctionMetric\FunctionMetricParser;
use Guard\Structure\Php\Token\TokenLineCounter;
use JsonException;
use PhpToken;
use RuntimeException;

/**
 * Reads PHP source and measures it without selecting or applying any policy.
 */
final class MetricParser implements \Guard\Structure\Structurer
{
    /** Derives metrics from the shared token structure.
     * @throws RuntimeException
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function structure(\Guard\Structure\Source $source): SourceMetrics
    {
        $tokens = $source->structure('php.tokens');
        if (!$tokens instanceof Tokens) {
            throw new \Guard\Diagnostic\PolicyException('Structure php.tokens must return Tokens. Register TokenParser for that id.');
        }
        return $this->measure($source->text(), $tokens->all(), '');
    }
    /**
     * Structures already-read source so additional policies can reuse the same metrics.
     */
    public function parse(string $source, string $relativePath): SourceMetrics
    {
        return $this->measure($source, array_values(PhpToken::tokenize($source)), $relativePath);
    }
    /** Measures source using tokens already produced during structuring.
     * @param list<PhpToken> $tokens
     */
    public function measure(string $source, array $tokens, string $relativePath): SourceMetrics
    {
        $counter = new TokenLineCounter();
        return new SourceMetrics(
            new FileMetric($relativePath, $counter->physicalLines($source), $counter->nonCommentLines($tokens)),
            (new ClassLikeMetricParser())->collect($tokens),
            (new FunctionMetricParser())->collect($tokens),
            true,
        );
    }
}
