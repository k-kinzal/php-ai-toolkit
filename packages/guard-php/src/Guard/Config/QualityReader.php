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
final class QualityReader
{
    /**
     * @param mixed $value
     * @param array<string, mixed> $scope
     */
    public function read($value, array $scope, string $root): LocGuardConfig
    {
        $schema = new Schema();
        $data = $schema->mapping($value, ['profiles', 'default', 'assignments'], 'quality');
        $policies = (new PolicyListConfigReader())->read($data['profiles'] ?? null);
        $apply = (new ApplyConfigReader())->read(['default' => $data['default'] ?? 'standard', 'rules' => $data['assignments'] ?? []], $policies);
        return new LocGuardConfig(
            $root,
            new ScanConfig($schema->strings($scope['source'] ?? ['src'], 'scope.source'), $schema->strings($scope['exclude'] ?? [], 'scope.exclude')),
            $policies,
            $apply,
            new ReportConfig('ai', ['path', 'line', 'rule']),
        );
    }
}
