<?php

declare(strict_types=1);

namespace Toolkit\Guard\Config;

use Toolkit\LocGuard\Config\LocGuardConfig;
use Toolkit\LocGuard\Config\Policy\ApplyConfigReader;
use Toolkit\LocGuard\Config\Policy\PolicyListConfigReader;
use Toolkit\LocGuard\Config\ReportConfig;
use Toolkit\LocGuard\Config\ScanConfig;

/**
 * Adapts metric profiles to the unchanged source metric analyzer.
 */
final class MetricsReader
{
    /**
     * @param mixed $value
     */
    public function read($value, string $root): LocGuardConfig
    {
        $schema = new Schema();
        $data = $schema->mapping($value, ['source', 'exclude', 'profiles', 'default', 'assignments'], 'metrics');
        $policies = (new PolicyListConfigReader())->read($data['profiles'] ?? null);
        $apply = (new ApplyConfigReader())->read(['default' => $data['default'] ?? 'standard', 'rules' => $data['assignments'] ?? []], $policies);
        return new LocGuardConfig(
            $root,
            new ScanConfig($schema->strings($data['source'] ?? ['src'], 'metrics.source'), $schema->strings($data['exclude'] ?? [], 'metrics.exclude')),
            $policies,
            $apply,
            new ReportConfig('ai', ['path', 'line', 'rule']),
        );
    }
}
