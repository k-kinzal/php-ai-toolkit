<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Config;

use function array_keys;
use function dirname;
use function in_array;
use function is_array;
use function is_file;

use PhpAiToolkit\DocGen\DocGenException;

use function realpath;
use function sprintf;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads and validates doc.yaml.
 */
final class ConfigLoader
{
    /** @var list<string> */
    private const KNOWN_KEYS = ['packages', 'vendor', 'vendor_dev', 'exclude', 'output', 'title', 'deptrac', 'coverage', 'cache', 'base_url', 'repository'];

    /** @readonly */
    private ConfigScalarReader $scalarReader;

    /** @readonly */
    private ConfigStringListReader $stringListReader;

    /** @readonly */
    private BaseUrl $baseUrl;

    /** @readonly */
    private RepositoryUrl $repository;

    /**
     * Creates a config loader from YAML section readers.
     */
    public function __construct(
        ?ConfigScalarReader $scalarReader = null,
        ?ConfigStringListReader $stringListReader = null,
        ?BaseUrl $baseUrl = null,
        ?RepositoryUrl $repository = null,
    ) {
        $this->scalarReader = $scalarReader ?? new ConfigScalarReader();
        $this->stringListReader = $stringListReader ?? new ConfigStringListReader();
        $this->baseUrl = $baseUrl ?? new BaseUrl();
        $this->repository = $repository ?? new RepositoryUrl();
    }

    /**
     * Loads and validates a DocGen YAML configuration file.
     *
     * @throws DocGenException when the file is missing, unparsable, or contains invalid values
     */
    public function load(string $path): DocGenConfig
    {
        if (!is_file($path)) {
            throw new DocGenException(sprintf('DocGen config not found: %s', $path));
        }

        try {
            $data = Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw new DocGenException('Invalid doc.yaml: ' . $exception->getMessage(), 0, $exception);
        }

        if (!is_array($data)) {
            throw new DocGenException('Invalid doc.yaml: top-level value must be a mapping.');
        }

        foreach (array_keys($data) as $key) {
            if (!in_array((string) $key, self::KNOWN_KEYS, true)) {
                throw new DocGenException(sprintf('Invalid doc.yaml: top-level contains unsupported key "%s".', $key));
            }
        }

        $root = realpath(dirname($path));

        return new DocGenConfig(
            $root === false ? dirname($path) : $root,
            $this->stringListReader->read($data, 'packages', ['.', 'packages/*']),
            $this->stringListReader->read($data, 'vendor', []),
            $this->stringListReader->read($data, 'exclude', []),
            $this->scalarReader->string($data, 'output', 'build/docs'),
            $this->scalarReader->optionalString($data, 'title'),
            $this->scalarReader->optionalString($data, 'deptrac'),
            $this->scalarReader->optionalString($data, 'coverage'),
            $this->stringListReader->read($data, 'vendor_dev', []),
            $this->scalarReader->optionalPath($data, 'cache', DocGenConfig::DEFAULT_CACHE),
            $this->baseUrl->normalize($this->scalarReader->optionalString($data, 'base_url')),
            $this->repository->normalize($this->scalarReader->optionalString($data, 'repository')),
        );
    }
}
