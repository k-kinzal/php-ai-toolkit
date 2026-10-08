<?php

declare(strict_types=1);

namespace Guard\Config\Project\Legacy;

use function dirname;

use Guard\Config\Profile\ApplyConfigReader;
use Guard\Config\Profile\PolicyListConfigReader;
use Guard\Config\Reader\ScanConfigReader;
use Guard\Config\Validation\MetricConfigKeyValidator;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\Definition\MetricsConfig;

use function is_array;
use function is_file;
use function sprintf;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads and validates loc.yaml.
 */
final class MetricConfiguration
{
    /** @readonly */
    private MetricConfigKeyValidator $keyValidator;

    /** @readonly */
    private ScanConfigReader $scanConfigReader;

    /** @readonly */
    private PolicyListConfigReader $policyListConfigReader;

    /** @readonly */
    private ApplyConfigReader $applyConfigReader;

    /** @readonly */
    private MetricReportReader $reportConfigReader;

    /**
     * Creates a config loader from YAML section readers.
     */
    public function __construct(
        ?MetricConfigKeyValidator $keyValidator = null,
        ?ScanConfigReader $scanConfigReader = null,
        ?PolicyListConfigReader $policyListConfigReader = null,
        ?ApplyConfigReader $applyConfigReader = null,
        ?MetricReportReader $reportConfigReader = null,
    ) {
        $this->keyValidator = $keyValidator ?? new MetricConfigKeyValidator();
        $this->scanConfigReader = $scanConfigReader ?? new ScanConfigReader();
        $this->policyListConfigReader = $policyListConfigReader ?? new PolicyListConfigReader();
        $this->applyConfigReader = $applyConfigReader ?? new ApplyConfigReader();
        $this->reportConfigReader = $reportConfigReader ?? new MetricReportReader();
    }

    /**
     * Loads and validates a metric policy YAML configuration file.
     *
     * @throws PolicyException when the file is missing, unparsable, or not a mapping
     */
    public function load(string $path): MetricsConfig
    {
        if (!is_file($path)) {
            throw new PolicyException(sprintf('LocGuard config not found: %s', $path));
        }

        try {
            $data = Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw new PolicyException('Invalid loc.yaml: ' . $exception->getMessage(), 0, $exception);
        }

        if (!is_array($data)) {
            throw new PolicyException('Invalid loc.yaml: top-level value must be a mapping.');
        }

        $this->keyValidator->rejectUnknown($data, ['scan', 'policies', 'apply', 'report'], 'top-level');
        $policies = $this->policyListConfigReader->read($data['policies'] ?? null);

        $config = new MetricsConfig(
            dirname($path),
            $this->scanConfigReader->read($data['scan'] ?? null),
            $policies,
            $this->applyConfigReader->read($data['apply'] ?? null, $policies),
        );
        $this->reportConfigReader->read($data['report'] ?? []);
        return $config;
    }
}
