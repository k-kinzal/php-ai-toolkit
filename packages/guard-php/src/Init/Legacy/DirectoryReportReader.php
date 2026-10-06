<?php

declare(strict_types=1);

namespace Guard\Init\Legacy;

use Guard\Config\Validation\DirectoryConfigScalarReader;
use Guard\Config\Validation\DirectoryConfigStringListReader;
use Guard\Policy\PolicyException;

use function implode;
use function in_array;
use function is_array;
use function sprintf;

/**
 * Reads directory policy report configuration from tree.yaml.
 */
final class DirectoryReportReader
{
    /** @var list<string> */
    private const REPORTERS = ['ai', 'text', 'json'];

    /** @var list<string> */
    private const ORDER_FIELDS = ['path', 'rule', 'actual', 'limit'];

    /** @readonly */
    private DirectoryConfigScalarReader $scalarReader;

    /** @readonly */
    private DirectoryConfigStringListReader $stringListReader;

    /**
     * Creates a reader from scalar and list validation.
     */
    public function __construct(
        ?DirectoryConfigScalarReader $scalarReader = null,
        ?DirectoryConfigStringListReader $stringListReader = null,
    ) {
        $this->scalarReader = $scalarReader ?? new DirectoryConfigScalarReader();
        $this->stringListReader = $stringListReader ?? new DirectoryConfigStringListReader();
    }

    /**
     * Reads report output configuration.
     *
     * @param mixed $value
     *
     * @throws PolicyException when the report section is not a mapping or contains unsupported values
     */
    public function read($value): DirectoryReportConfig
    {
        if (!is_array($value)) {
            throw new PolicyException('Invalid tree.yaml: "report" must be a mapping.');
        }

        $reporter = $this->scalarReader->string($value, 'reporter', 'ai', 'report');
        if (!in_array($reporter, self::REPORTERS, true)) {
            throw new PolicyException(sprintf('Invalid tree.yaml: "report.reporter" must be one of: %s.', implode(', ', self::REPORTERS)));
        }

        $orderBy = $this->stringListReader->read($value, 'order_by', ['path', 'rule'], 'report');
        foreach ($orderBy as $field) {
            if (!in_array($field, self::ORDER_FIELDS, true)) {
                throw new PolicyException(sprintf('Invalid tree.yaml: "report.order_by" contains unsupported field "%s".', $field));
            }
        }

        return new DirectoryReportConfig($reporter, $orderBy);
    }
}
