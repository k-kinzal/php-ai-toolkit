<?php

declare(strict_types=1);

namespace Guard\Collect\Tree;

use Guard\Collect\Collector;
use Guard\Collect\Tree\Filesystem\DirectoryTreeScanner;
use Guard\Execution\Context;

/**
 * Collects a directory tree without applying directory constraints.
 */
final class TreeCollector implements Collector
{
    /**
     * @return list<DirectoryTree>
     */
    public function collect(Context $context): array
    {
        $config = $context->configuration->structure;
        return $config === null ? [] : [new DirectoryTree((new DirectoryTreeScanner())->scan($config))];
    }
}
