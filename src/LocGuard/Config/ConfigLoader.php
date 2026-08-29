<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Config;

use function dirname;
use function is_array;
use function is_file;
use function sprintf;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Toolkit\LocGuard\Config\Policy\ApplyConfigReader;
use Toolkit\LocGuard\Config\Policy\PolicyListConfigReader;
use Toolkit\LocGuard\LocGuardException;

/**
 * Loads and validates loc.yaml.
 */
final class ConfigLoader
{
    /** @readonly */
    private ConfigKeyValidator $keyValidator;

    /** @readonly */
    private ScanConfigReader $scanConfigReader;

    /** @readonly */
    private PolicyListConfigReader $policyListConfigReader;

    /** @readonly */
    private ApplyConfigReader $applyConfigReader;

    /** @readonly */
    private ReportConfigReader $reportConfigReader;

    /**
     * Creates a config loader from YAML section readers.
     */
    public function __construct(
        ?ConfigKeyValidator $keyValidator = null,
        ?ScanConfigReader $scanConfigReader = null,
        ?PolicyListConfigReader $policyListConfigReader = null,
        ?ApplyConfigReader $applyConfigReader = null,
        ?ReportConfigReader $reportConfigReader = null,
    ) {
        $this->keyValidator = $keyValidator ?? new ConfigKeyValidator();
        $this->scanConfigReader = $scanConfigReader ?? new ScanConfigReader();
        $this->policyListConfigReader = $policyListConfigReader ?? new PolicyListConfigReader();
        $this->applyConfigReader = $applyConfigReader ?? new ApplyConfigReader();
        $this->reportConfigReader = $reportConfigReader ?? new ReportConfigReader();
    }

    /**
     * Loads and validates a LocGuard YAML configuration file.
     *
     * @throws LocGuardException when the file is missing, unparsable, or not a mapping
     */
    public function load(string $path): LocGuardConfig
    {
        if (!is_file($path)) {
            throw new LocGuardException(sprintf('LocGuard config not found: %s', $path));
        }

        try {
            $data = Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw new LocGuardException('Invalid loc.yaml: ' . $exception->getMessage(), 0, $exception);
        }

        if (!is_array($data)) {
            throw new LocGuardException('Invalid loc.yaml: top-level value must be a mapping.');
        }

        $this->keyValidator->rejectUnknown($data, ['scan', 'policies', 'apply', 'report'], 'top-level');
        $policies = $this->policyListConfigReader->read($data['policies'] ?? null);

        return new LocGuardConfig(
            dirname($path),
            $this->scanConfigReader->read($data['scan'] ?? null),
            $policies,
            $this->applyConfigReader->read($data['apply'] ?? null, $policies),
            $this->reportConfigReader->read($data['report'] ?? []),
        );
    }
}
