<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Cli;

use PhpAiToolkit\LocGuard\Config\LocGuardConfig;
use PhpAiToolkit\LocGuard\Config\ReportConfig;

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
            $config->paths,
            $config->exclude,
            $config->limits,
            new ReportConfig($reporter, $config->report->orderBy),
        );
    }
}
