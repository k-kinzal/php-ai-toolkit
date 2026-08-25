<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Cli;

use Toolkit\ScopeGuard\Config\ReportConfig;
use Toolkit\ScopeGuard\Config\ScopeGuardConfig;

/**
 * Applies a CLI reporter override to ScopeGuard config.
 *
 * @visibility namespace
 */
final class ScopeGuardReporterOverride
{
    /**
     * Returns config with the reporter override applied when present.
     */
    public function apply(ScopeGuardConfig $config, ?string $reporter): ScopeGuardConfig
    {
        if ($reporter === null) {
            return $config;
        }

        return new ScopeGuardConfig(
            $config->root,
            $config->paths,
            $config->exclude,
            $config->exemptNamespaces,
            new ReportConfig($reporter, $config->report->orderBy),
        );
    }
}
