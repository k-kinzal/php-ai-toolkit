<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Cli;

use Toolkit\LocGuard\Config\LocGuardConfig;
use Toolkit\LocGuard\Config\ReportConfig;

/**
 * Applies a CLI reporter override to LocGuard config.
 */
final class LocGuardReporterOverride
{
    /**
     * Returns config with the reporter override applied when present.
     */
    public function apply(LocGuardConfig $config, ?string $reporter): LocGuardConfig
    {
        if ($reporter === null) {
            return $config;
        }

        return new LocGuardConfig(
            $config->root,
            $config->scan,
            $config->policies,
            $config->apply,
            new ReportConfig($reporter, $config->report->orderBy),
        );
    }
}
