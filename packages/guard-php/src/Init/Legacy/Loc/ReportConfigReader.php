<?php

declare(strict_types=1);

namespace Guard\Init\Legacy\Loc;

use Guard\Config\Loc\ConfigKeyValidator;
use Guard\Config\Loc\ConfigScalarReader;
use Guard\Config\Loc\ConfigStringListReader;
use Guard\Policy\PolicyException;

use function implode;
use function in_array;
use function is_array;
use function sprintf;

/**
 * Reads LocGuard report configuration from loc.yaml.
 */
final class ReportConfigReader
{
    /** @var list<string> */
    private const REPORTERS = ['ai', 'text', 'json'];

    /** @var list<string> */
    private const ORDER_FIELDS = ['path', 'line', 'rule', 'actual', 'limit'];

    /** @readonly */
    private ConfigScalarReader $scalarReader;

    /** @readonly */
    private ConfigStringListReader $stringListReader;

    /** @readonly */
    private ConfigKeyValidator $keyValidator;

    /**
     * Creates a reader from scalar and list validation.
     */
    public function __construct(
        ?ConfigScalarReader $scalarReader = null,
        ?ConfigStringListReader $stringListReader = null,
        ?ConfigKeyValidator $keyValidator = null,
    ) {
        $this->scalarReader = $scalarReader ?? new ConfigScalarReader();
        $this->stringListReader = $stringListReader ?? new ConfigStringListReader();
        $this->keyValidator = $keyValidator ?? new ConfigKeyValidator();
    }

    /**
     * Reads report output configuration.
     *
     * @param mixed $value
     *
     * @throws PolicyException when the report section is not a mapping or contains unsupported values
     */
    public function read($value): ReportConfig
    {
        if (!is_array($value)) {
            throw new PolicyException('Invalid loc.yaml: "report" must be a mapping.');
        }
        $this->keyValidator->rejectUnknown($value, ['reporter', 'order_by'], 'report');

        $reporter = $this->scalarReader->string($value, 'reporter', 'ai', 'report');
        if (!in_array($reporter, self::REPORTERS, true)) {
            throw new PolicyException(sprintf('Invalid loc.yaml: "report.reporter" must be one of: %s.', implode(', ', self::REPORTERS)));
        }

        $orderBy = $this->stringListReader->read($value, 'order_by', ['path', 'line', 'rule'], 'report');
        foreach ($orderBy as $field) {
            if (!in_array($field, self::ORDER_FIELDS, true)) {
                throw new PolicyException(sprintf('Invalid loc.yaml: "report.order_by" contains unsupported field "%s".', $field));
            }
        }

        return new ReportConfig($reporter, $orderBy);
    }
}
