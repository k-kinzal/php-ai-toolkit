<?php

declare(strict_types=1);

namespace Guard\Config\Reader;

use Guard\Config\Profile\ApplyConfigReader;
use Guard\Config\Profile\PolicyListConfigReader;
use Guard\Config\Schema;
use Guard\Policy\Definition\MetricsConfig;
use Guard\Policy\Definition\ScanConfig;

/**
 * Reads source selection and metric profiles for PHP structures and MetricLimits.
 */
final class MetricPolicyReader
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
