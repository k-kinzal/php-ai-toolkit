<?php

declare(strict_types=1);

namespace Guard\Cli;

use Guard\Policy\PolicyException;
use Guard\Reporting\Baseline;
use JsonException;

/**
 * Reads and atomically replaces baseline files independently of project repairs.
 */
final class BaselineFile
{
    /**
     * The baseline automatically used beside the selected policy file.
     */
    public const NAME = 'guard-baseline.json';

    /**
     * Resolves baseline paths relative to the policy file, including a custom output name.
     * @throws PolicyException
     */
    public function path(string $config, ?string $path): string
    {
        if ($path !== null && trim($path) === '') {
            throw new PolicyException('Provide a non-empty baseline path, such as ' . self::NAME . '.');
        }
        $path = $path ?? self::NAME;
        return str_starts_with($path, '/') ? $path : dirname($config) . '/' . $path;
    }

    /**
     * @throws PolicyException
     */
    public function read(string $path): string
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new PolicyException('Cannot read baseline ' . $path . '. Generate it with guard baseline or correct --baseline.');
        }
        $json = file_get_contents($path);
        if ($json === false) {
            throw new PolicyException('Cannot read baseline ' . $path . '. Check file permissions.');
        }
        return $json;
    }

    /**
     * Refuses to overwrite an unrelated document or a symlink; stages before replacing.
     * @throws PolicyException
     */
    public function write(string $path, string $json): void
    {
        $this->validateTarget($path);
        $original = $this->current($path);
        if ($original !== null) {
            $this->validate($original, $path);
        }
        $temporary = tempnam(dirname($path), '.guard-baseline-');
        if ($temporary === false) {
            throw new PolicyException('Cannot stage baseline ' . $path . '. Check directory permissions.');
        }
        try {
            if (file_put_contents($temporary, $json) !== strlen($json)) {
                throw new PolicyException('Cannot stage baseline ' . $path . '. Check available disk space.');
            }
            $this->validateTarget($path);
            if ($this->current($path) !== $original) {
                throw new PolicyException('Baseline changed during generation: ' . $path . '. Review it and run guard baseline again.');
            }
            if (!rename($temporary, $path)) {
                throw new PolicyException('Cannot replace baseline ' . $path . '. Check directory permissions.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    /**
     * Reports invalid JSON and schema errors with the baseline path and a recovery action.
     * @throws PolicyException
     */
    public function validate(string $json, string $path): void
    {
        try {
            (new Baseline())->entries($json);
        } catch (JsonException | PolicyException $exception) {
            throw new PolicyException('Invalid baseline ' . $path . ': ' . $exception->getMessage() . ' Correct this file or generate a new baseline with guard baseline --output at another path.');
        }
    }

    /**
     * Reads the target again after staging so concurrent changes cannot be overwritten.
     * @throws PolicyException
     */
    public function current(string $path): ?string
    {
        clearstatcache(true, $path);
        return is_file($path) ? $this->read($path) : null;
    }

    /**
     * Checks that staging and replacement can target a regular file in an existing directory.
     * @throws PolicyException
     */
    public function validateTarget(string $path): void
    {
        if (!is_dir(dirname($path)) || !is_writable(dirname($path)) || is_link($path) || (file_exists($path) && !is_file($path))) {
            throw new PolicyException('Cannot write baseline ' . $path . '. Choose a regular file in an existing writable directory, without a symlink.');
        }
    }
}
