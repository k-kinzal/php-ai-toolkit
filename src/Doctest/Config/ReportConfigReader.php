<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Config;

use function implode;
use function in_array;
use function is_array;

use PhpAiToolkit\Doctest\DoctestException;

use function sprintf;

/**
 * Reads doctest report configuration from doctest.yaml.
 *
 * @visibility namespace
 */
final class ReportConfigReader
{
    /** @var list<string> */
    private const REPORTERS = ['ai', 'text', 'json'];

    /** @var list<string> */
    private const ORDER_FIELDS = ['path', 'line', 'symbol'];

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
     *
     * @throws DoctestException when the report section is not a mapping or contains unsupported values
     */
    public function read($value): ReportConfig
    {
        if (!is_array($value)) {
            throw new DoctestException('Invalid doctest.yaml: "report" must be a mapping.');
        }

        $reporter = $this->scalarReader->string($value, 'reporter', 'ai', 'report');
        if (!in_array($reporter, self::REPORTERS, true)) {
            throw new DoctestException(sprintf('Invalid doctest.yaml: "report.reporter" must be one of: %s.', implode(', ', self::REPORTERS)));
        }

        $orderBy = $this->stringListReader->read($value, 'order_by', ['path', 'line'], 'report');
        foreach ($orderBy as $field) {
            if (!in_array($field, self::ORDER_FIELDS, true)) {
                throw new DoctestException(sprintf('Invalid doctest.yaml: "report.order_by" contains unsupported field "%s".', $field));
            }
        }

        return new ReportConfig($reporter, $orderBy);
    }
}
