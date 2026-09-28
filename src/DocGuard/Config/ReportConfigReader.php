<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Config;

use function implode;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;

use Toolkit\DocGuard\DocGuardException;

/**
 * Reads DocGuard report configuration from doc-guard.yaml.
 */
final class ReportConfigReader
{
    /** @var list<string> */
    private const REPORTERS = ['ai', 'text', 'json'];

    /** @var list<string> */
    private const ORDER_FIELDS = ['path', 'line', 'rule'];

    /** @readonly */
    private ConfigKeyValidator $keyValidator;

    /** @readonly */
    private ConfigStringListReader $stringListReader;

    /**
     * Creates a reader from key and list validation.
     */
    public function __construct(?ConfigKeyValidator $keyValidator = null, ?ConfigStringListReader $stringListReader = null)
    {
        $this->keyValidator = $keyValidator ?? new ConfigKeyValidator();
        $this->stringListReader = $stringListReader ?? new ConfigStringListReader();
    }

    /**
     * Reads report output configuration.
     *
     * @param mixed $value
     *
     * @throws DocGuardException when the report section is not a mapping or contains unsupported values
     */
    public function read($value): ReportConfig
    {
        if (!is_array($value)) {
            throw new DocGuardException('Invalid doc-guard.yaml: "report" must be a mapping.');
        }

        $this->keyValidator->rejectUnknown($value, ['reporter', 'order_by'], 'report');

        $reporter = $value['reporter'] ?? 'ai';
        if (!is_string($reporter) || !in_array($reporter, self::REPORTERS, true)) {
            throw new DocGuardException(sprintf('Invalid doc-guard.yaml: "report.reporter" must be one of: %s.', implode(', ', self::REPORTERS)));
        }

        $orderBy = $this->stringListReader->read($value, 'order_by', self::ORDER_FIELDS, 'report');
        foreach ($orderBy as $field) {
            if (!in_array($field, self::ORDER_FIELDS, true)) {
                throw new DocGuardException(sprintf('Invalid doc-guard.yaml: "report.order_by" contains unsupported field "%s". Supported fields: %s.', $field, implode(', ', self::ORDER_FIELDS)));
            }
        }

        return new ReportConfig($reporter, $orderBy);
    }
}
