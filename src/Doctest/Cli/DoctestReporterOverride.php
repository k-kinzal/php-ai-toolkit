<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Cli;

use PhpAiToolkit\Doctest\Config\DoctestConfig;
use PhpAiToolkit\Doctest\Config\ReportConfig;

/**
 * Applies a CLI reporter override to doctest config.
 *
 * @visibility namespace
 */
final class DoctestReporterOverride
{
    /**
     * Returns config with the reporter override applied when present.
     */
    public function apply(DoctestConfig $config, ?string $reporter): DoctestConfig
    {
        if ($reporter === null) {
            return $config;
        }

        return new DoctestConfig(
            $config->root,
            $config->paths,
            $config->exclude,
            $config->bootstrap,
            new ReportConfig($reporter, $config->report->orderBy),
        );
    }
}
