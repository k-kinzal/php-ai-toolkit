<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Config;

use function is_array;

use Toolkit\LocGuard\LocGuardException;

/**
 * Reads source discovery configuration from loc.yaml.
 */
final class ScanConfigReader
{
    /** @readonly */
    private ConfigKeyValidator $keyValidator;

    /** @readonly */
    private ConfigStringListReader $stringListReader;

    /**
     * Creates a scan reader from mapping and string-list validation.
     */
    public function __construct(
        ?ConfigKeyValidator $keyValidator = null,
        ?ConfigStringListReader $stringListReader = null,
    ) {
        $this->keyValidator = $keyValidator ?? new ConfigKeyValidator();
        $this->stringListReader = $stringListReader ?? new ConfigStringListReader();
    }

    /**
     * Reads exact source roots and path-pattern exclusions.
     *
     * @param mixed $value
     *
     * @throws LocGuardException when scan configuration is invalid
     */
    public function read($value): ScanConfig
    {
        if (!is_array($value)) {
            throw new LocGuardException('Invalid loc.yaml: "scan" must be a mapping.');
        }

        $this->keyValidator->rejectUnknown($value, ['roots', 'exclude'], 'scan');

        return new ScanConfig(
            $this->stringListReader->readRequired($value, 'roots', 'scan', false),
            $this->stringListReader->read($value, 'exclude', [], 'scan'),
        );
    }
}
