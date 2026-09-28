<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Config;

/**
 * Fully resolved DocGuard configuration.
 *
 * @property-read string $root
 * @property-read string $configName
 * @property-read list<DocumentConfig> $documents
 * @property-read list<string> $scan
 * @property-read ReportConfig $report
 */
final class DocGuardConfig
{
    /**
     * @param list<DocumentConfig> $documents
     * @param list<string> $scan
     */
    public function __construct(
        /** @readonly */
        private string $root,
        /** @readonly */
        private string $configName,
        /** @readonly */
        private array $documents,
        /** @readonly */
        private array $scan,
        /** @readonly */
        private ReportConfig $report,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'root' => $this->root,
            'configName' => $this->configName,
            'documents' => $this->documents,
            'scan' => $this->scan,
            'report' => $this->report,
            default => null,
        };
    }
}
