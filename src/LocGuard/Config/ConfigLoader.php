<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Config;

use function dirname;
use function implode;
use function in_array;
use function is_array;
use function is_file;
use function is_int;
use function is_string;

use PhpAiToolkit\LocGuard\LocGuardException;

use function sprintf;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads and validates loc.yaml.
 */
final class ConfigLoader
{
    /** @var list<string> */
    private const REPORTERS = ['ai', 'text', 'json'];

    /** @var list<string> */
    private const ORDER_FIELDS = ['path', 'line', 'rule', 'actual', 'limit'];

    /**
     * Loads and validates a LocGuard YAML configuration file.
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

        return new LocGuardConfig(
            dirname($path),
            $this->readStringList($data, 'paths', ['src']),
            $this->readStringList($data, 'exclude', []),
            $this->readLimits($data['limits'] ?? []),
            $this->readReport($data['report'] ?? []),
        );
    }

    /**
     * @param array<mixed> $data
     * @param list<string> $default
     * @return list<string>
     */
    private function readStringList(array $data, string $key, array $default): array
    {
        $value = $data[$key] ?? $default;
        if (!is_array($value)) {
            throw new LocGuardException(sprintf('Invalid loc.yaml: "%s" must be a list of strings.', $key));
        }

        $strings = [];
        foreach ($value as $entry) {
            if (!is_string($entry) || $entry === '') {
                throw new LocGuardException(sprintf('Invalid loc.yaml: "%s" must be a list of strings.', $key));
            }
            $strings[] = $entry;
        }

        return $strings;
    }

    /**
     * @param mixed $value
     */
    private function readLimits($value): LimitConfig
    {
        if (!is_array($value)) {
            throw new LocGuardException('Invalid loc.yaml: "limits" must be a mapping.');
        }

        return new LimitConfig(
            $this->readPositiveInt($value, 'max_file_lines', 500),
            $this->readPositiveInt($value, 'max_file_ncloc', 350),
            $this->readPositiveInt($value, 'max_class_lines', 400),
            $this->readPositiveInt($value, 'max_trait_lines', 300),
            $this->readPositiveInt($value, 'max_interface_lines', 200),
            $this->readPositiveInt($value, 'max_enum_lines', 200),
            $this->readPositiveInt($value, 'max_function_lines', 50),
            $this->readPositiveInt($value, 'max_method_lines', 50),
            $this->readPositiveInt($value, 'max_cyclomatic_complexity', 20),
        );
    }

    /**
     * @param mixed $value
     */
    private function readReport($value): ReportConfig
    {
        if (!is_array($value)) {
            throw new LocGuardException('Invalid loc.yaml: "report" must be a mapping.');
        }

        $reporter = $this->readString($value, 'reporter', 'ai');
        if (!in_array($reporter, self::REPORTERS, true)) {
            throw new LocGuardException(sprintf('Invalid loc.yaml: "report.reporter" must be one of: %s.', implode(', ', self::REPORTERS)));
        }

        $orderBy = $this->readStringList($value, 'order_by', ['path', 'line', 'rule']);
        foreach ($orderBy as $field) {
            if (!in_array($field, self::ORDER_FIELDS, true)) {
                throw new LocGuardException(sprintf('Invalid loc.yaml: "report.order_by" contains unsupported field "%s".', $field));
            }
        }

        return new ReportConfig($reporter, $orderBy);
    }

    /**
     * @param array<mixed> $data
     */
    private function readString(array $data, string $key, string $default): string
    {
        $value = $data[$key] ?? $default;
        if (!is_string($value) || $value === '') {
            throw new LocGuardException(sprintf('Invalid loc.yaml: "%s" must be a non-empty string.', $key));
        }

        return $value;
    }

    /**
     * @param array<mixed> $data
     */
    private function readPositiveInt(array $data, string $key, int $default): int
    {
        $value = $data[$key] ?? $default;
        if (!is_int($value) || $value < 1) {
            throw new LocGuardException(sprintf('Invalid loc.yaml: "limits.%s" must be a positive integer.', $key));
        }

        return $value;
    }
}
