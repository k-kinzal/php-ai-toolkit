<?php

declare(strict_types=1);

namespace Guard\Config;

use Guard\Config\Loc\MetricsConfig;
use Guard\Config\Loc\Policy\ApplyConfigReader;
use Guard\Config\Loc\Policy\PolicyListConfigReader;
use Guard\Config\Loc\ScanConfig;

/**
 * Reads source selection and metric profiles for the PHP collector and LocPolicy.
 */
final class MetricsReader
{
    /**
     * @param mixed $value
     */
    public function read($value, string $root): MetricsConfig
    {
        $schema = new Schema();
        $data = $schema->mapping($value, ['source', 'exclude', 'profiles', 'default', 'assignments'], 'metrics');
        $policies = (new PolicyListConfigReader())->read($data['profiles'] ?? null);
        $apply = (new ApplyConfigReader())->read(['default' => $data['default'] ?? 'standard', 'rules' => $data['assignments'] ?? []], $policies);
        return new MetricsConfig(
            $root,
            new ScanConfig($schema->strings($data['source'] ?? ['src'], 'metrics.source'), $schema->strings($data['exclude'] ?? [], 'metrics.exclude')),
            $policies,
            $apply,
        );
    }
}
