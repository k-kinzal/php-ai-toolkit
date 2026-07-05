<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Config;

use function implode;
use function in_array;
use function is_array;

use PhpAiToolkit\LocGuard\LocGuardException;

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

    /**
     * Creates a reader from scalar and list validation.
     */
    public function __construct(
        ?ConfigScalarReader $scalarReader = null,
        ?ConfigStringListReader $stringListReader = null,
    ) {
        $this->scalarReader = $scalarReader ?? new ConfigScalarReader();
        $this->stringListReader = $stringListReader ?? new ConfigStringListReader();
    }

    /**
     * Reads report output configuration.
     *
     * @param mixed $value
     */
    public function read($value): ReportConfig
    {
        if (!is_array($value)) {
            throw new LocGuardException('Invalid loc.yaml: "report" must be a mapping.');
        }

        $reporter = $this->scalarReader->string($value, 'reporter', 'ai');
        if (!in_array($reporter, self::REPORTERS, true)) {
            throw new LocGuardException(sprintf('Invalid loc.yaml: "report.reporter" must be one of: %s.', implode(', ', self::REPORTERS)));
        }

        $orderBy = $this->stringListReader->read($value, 'order_by', ['path', 'line', 'rule']);
        foreach ($orderBy as $field) {
            if (!in_array($field, self::ORDER_FIELDS, true)) {
                throw new LocGuardException(sprintf('Invalid loc.yaml: "report.order_by" contains unsupported field "%s".', $field));
            }
        }

        return new ReportConfig($reporter, $orderBy);
    }
}
