<?php

declare(strict_types=1);

namespace Guard\Collect\Php;

use Guard\Collect\Collector;
use Guard\Collect\Php\Filesystem\PhpFileFinder;
use Guard\Execution\Context;

/**
 * Collects each selected PHP source once for all registered source policies.
 */
final class PhpCollector implements Collector
{
    /**
     * @return list<PhpSources>
     */
    public function collect(Context $context): array
    {
        $config = $context->configuration->metrics;
        if ($config === null) {
            return [];
        }
        $paths = (new PhpFileFinder())->find($config);
        $metrics = [];
        $reader = new SourceMetricReader();
        foreach ($paths as $path => $relativePath) {
            $metrics[$path] = $reader->read($path, $relativePath);
        }
        return [new PhpSources($paths, $metrics)];
    }
}
