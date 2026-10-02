<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Config;

use function basename;
use function dirname;
use function is_array;
use function is_file;
use function sprintf;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Toolkit\DocGuard\DocGuardException;

/**
 * Loads and validates doc-guard.yaml.
 */
final class ConfigLoader
{
    /** @readonly */
    private ConfigKeyValidator $keyValidator;

    /** @readonly */
    private DocumentListConfigReader $documentListReader;

    /** @readonly */
    private ConfigStringListReader $stringListReader;

    /** @readonly */
    private ReportConfigReader $reportConfigReader;

    /**
     * Creates a config loader from YAML section readers.
     */
    public function __construct(
        ?ConfigKeyValidator $keyValidator = null,
        ?DocumentListConfigReader $documentListReader = null,
        ?ConfigStringListReader $stringListReader = null,
        ?ReportConfigReader $reportConfigReader = null,
    ) {
        $this->keyValidator = $keyValidator ?? new ConfigKeyValidator();
        $this->documentListReader = $documentListReader ?? new DocumentListConfigReader();
        $this->stringListReader = $stringListReader ?? new ConfigStringListReader();
        $this->reportConfigReader = $reportConfigReader ?? new ReportConfigReader();
    }

    /**
     * Loads and validates a DocGuard YAML configuration file.
     *
     * @throws DocGuardException when the file is missing, unparsable, or not a valid configuration
     */
    public function load(string $path): DocGuardConfig
    {
        if (!is_file($path)) {
            throw new DocGuardException(sprintf('DocGuard config not found: %s. Ask a human to create it, for example with: doc-guard --generate > doc-guard.yaml', $path));
        }

        try {
            $data = Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw new DocGuardException('Invalid doc-guard.yaml: ' . $exception->getMessage(), 0, $exception);
        }

        if (!is_array($data)) {
            throw new DocGuardException('Invalid doc-guard.yaml: top-level value must be a mapping.');
        }

        $this->keyValidator->rejectUnknown($data, ['documents', 'scan', 'report'], 'top-level');

        return new DocGuardConfig(
            dirname($path),
            basename($path),
            $this->documentListReader->read($data['documents'] ?? null),
            $this->stringListReader->read($data, 'scan', [], ''),
            $this->reportConfigReader->read($data['report'] ?? []),
        );
    }
}
