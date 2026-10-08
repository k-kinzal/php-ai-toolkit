<?php

declare(strict_types=1);

namespace Guard\Config\Project\Legacy;

use function basename;
use function dirname;

use Guard\Config\Reader\DocumentListConfigReader;
use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Config\Validation\HeadingConfigStringListReader;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\Definition\DocumentationConfig;

use function is_array;
use function is_file;
use function sprintf;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads and validates doc-guard.yaml.
 */
final class HeadingConfiguration
{
    /** @readonly */
    private HeadingConfigKeyValidator $keyValidator;

    /** @readonly */
    private DocumentListConfigReader $documentListReader;

    /** @readonly */
    private HeadingConfigStringListReader $stringListReader;

    /** @readonly */
    private HeadingReportReader $reportConfigReader;

    /**
     * Creates a config loader from YAML section readers.
     */
    public function __construct(
        ?HeadingConfigKeyValidator $keyValidator = null,
        ?DocumentListConfigReader $documentListReader = null,
        ?HeadingConfigStringListReader $stringListReader = null,
        ?HeadingReportReader $reportConfigReader = null,
    ) {
        $this->keyValidator = $keyValidator ?? new HeadingConfigKeyValidator();
        $this->documentListReader = $documentListReader ?? new DocumentListConfigReader();
        $this->stringListReader = $stringListReader ?? new HeadingConfigStringListReader();
        $this->reportConfigReader = $reportConfigReader ?? new HeadingReportReader();
    }

    /**
     * Loads and validates a heading policy YAML configuration file.
     *
     * @throws PolicyException when the file is missing, unparsable, or not a valid configuration
     */
    public function load(string $path): DocumentationConfig
    {
        if (!is_file($path)) {
            throw new PolicyException(sprintf('DocGuard config not found: %s. Ask a human to create it, for example with: doc-guard --generate > doc-guard.yaml', $path));
        }

        try {
            $data = Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw new PolicyException('Invalid doc-guard.yaml: ' . $exception->getMessage(), 0, $exception);
        }

        if (!is_array($data)) {
            throw new PolicyException('Invalid doc-guard.yaml: top-level value must be a mapping.');
        }

        $this->keyValidator->rejectUnknown($data, ['documents', 'scan', 'report'], 'top-level');

        $config = new DocumentationConfig(
            dirname($path),
            basename($path),
            $this->documentListReader->read($data['documents'] ?? null),
            $this->stringListReader->read($data, 'scan', [], ''),
        );
        $this->reportConfigReader->read($data['report'] ?? []);
        return $config;
    }
}
