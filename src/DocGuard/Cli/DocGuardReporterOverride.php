<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Cli;

use Toolkit\DocGuard\Config\DocGuardConfig;
use Toolkit\DocGuard\Config\ReportConfig;

/**
 * Applies a CLI reporter override to DocGuard config.
 */
final class DocGuardReporterOverride
{
    /**
     * Returns config with the reporter override applied when present.
     */
    public function apply(DocGuardConfig $config, ?string $reporter): DocGuardConfig
    {
        if ($reporter === null) {
            return $config;
        }

        return new DocGuardConfig(
            $config->root,
            $config->configName,
            $config->documents,
            $config->scan,
            new ReportConfig($reporter, $config->report->orderBy),
        );
    }
}
