<?php

declare(strict_types=1);

namespace Guard\Init\Legacy;

use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Config\Validation\HeadingConfigStringListReader;
use Guard\Policy\PolicyException;

use function implode;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Reads heading policy report configuration from doc-guard.yaml.
 */
final class HeadingReportReader
{
    /** @var list<string> */
    private const REPORTERS = ['ai', 'text', 'json'];

    /** @var list<string> */
    private const ORDER_FIELDS = ['path', 'line', 'rule'];

    /** @readonly */
    private HeadingConfigKeyValidator $keyValidator;

    /** @readonly */
    private HeadingConfigStringListReader $stringListReader;

    /**
     * Creates a reader from key and list validation.
     */
    public function __construct(?HeadingConfigKeyValidator $keyValidator = null, ?HeadingConfigStringListReader $stringListReader = null)
    {
        $this->keyValidator = $keyValidator ?? new HeadingConfigKeyValidator();
        $this->stringListReader = $stringListReader ?? new HeadingConfigStringListReader();
    }

    /**
     * Reads report output configuration.
     *
     * @param mixed $value
     *
     * @throws PolicyException when the report section is not a mapping or contains unsupported values
     */
    public function read($value): HeadingReportConfig
    {
        if (!is_array($value)) {
            throw new PolicyException('Invalid doc-guard.yaml: "report" must be a mapping.');
        }

        $this->keyValidator->rejectUnknown($value, ['reporter', 'order_by'], 'report');

        $reporter = $value['reporter'] ?? 'ai';
        if (!is_string($reporter) || !in_array($reporter, self::REPORTERS, true)) {
            throw new PolicyException(sprintf('Invalid doc-guard.yaml: "report.reporter" must be one of: %s.', implode(', ', self::REPORTERS)));
        }

        $orderBy = $this->stringListReader->read($value, 'order_by', self::ORDER_FIELDS, 'report');
        foreach ($orderBy as $field) {
            if (!in_array($field, self::ORDER_FIELDS, true)) {
                throw new PolicyException(sprintf('Invalid doc-guard.yaml: "report.order_by" contains unsupported field "%s". Supported fields: %s.', $field, implode(', ', self::ORDER_FIELDS)));
            }
        }

        return new HeadingReportConfig($reporter, $orderBy);
    }
}
