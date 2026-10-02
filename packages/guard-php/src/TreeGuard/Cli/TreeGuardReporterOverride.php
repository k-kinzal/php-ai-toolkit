<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Cli;

use Toolkit\TreeGuard\Config\ReportConfig;
use Toolkit\TreeGuard\Config\TreeGuardConfig;

/**
 * Applies a CLI reporter override to TreeGuard config.
 */
final class TreeGuardReporterOverride
{
    /**
     * Returns config with the reporter override applied when present.
     */
    public function apply(TreeGuardConfig $config, ?string $reporter): TreeGuardConfig
    {
        if ($reporter === null) {
            return $config;
        }

        return new TreeGuardConfig(
            $config->root,
            $config->paths,
            $config->exclude,
            $config->rules,
            new ReportConfig($reporter, $config->report->orderBy),
        );
    }
}
