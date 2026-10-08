<?php

declare(strict_types=1);

namespace Guard\Config\Reader;

use function array_key_exists;
use function array_keys;

use Guard\Config\Validation\MetricConfigKeyValidator;
use Guard\Config\Validation\MetricConfigScalarReader;
use Guard\Diagnostic\PolicyException;

use function is_array;
use function sprintf;

/**
 * Reads nested optional metric limits from one policy.
 */
final class LimitConfigReader
{
    /** @var array<string, list<string>> */
    private const METRICS = [
        'file' => ['lines', 'ncloc'],
        'class' => ['lines'],
        'trait' => ['lines'],
        'interface' => ['lines'],
        'enum' => ['lines'],
        'function' => ['lines', 'cyclomatic_complexity'],
        'method' => ['lines', 'cyclomatic_complexity'],
    ];

    /** @readonly */
    private MetricConfigKeyValidator $keyValidator;

    /** @readonly */
    private MetricConfigScalarReader $scalarReader;

    /**
     * Creates a limit reader from mapping and scalar validation.
     */
    public function __construct(
        ?MetricConfigKeyValidator $keyValidator = null,
        ?MetricConfigScalarReader $scalarReader = null,
    ) {
        $this->keyValidator = $keyValidator ?? new MetricConfigKeyValidator();
        $this->scalarReader = $scalarReader ?? new MetricConfigScalarReader();
    }

    /**
     * Reads a partial limit mapping while preserving explicit null values.
     *
     * @param mixed $value
     * @return array<string, ?int>
     *
     * @throws PolicyException when a limit mapping or value is invalid
     */
    public function read($value, string $context = 'limits'): array
    {
        if (!is_array($value)) {
            throw new PolicyException(sprintf('Invalid loc.yaml: "%s" must be a mapping.', $context));
        }
        $this->keyValidator->rejectUnknown($value, array_keys(self::METRICS), $context);

        $limits = [];
        foreach (self::METRICS as $subject => $metrics) {
            if (!array_key_exists($subject, $value)) {
                continue;
            }
            if (!is_array($value[$subject])) {
                throw new PolicyException(sprintf(
                    'Invalid loc.yaml: "%s.%s" must be a mapping.',
                    $context,
                    $subject,
                ));
            }

            $subjectContext = $context . '.' . $subject;
            $this->keyValidator->rejectUnknown($value[$subject], $metrics, $subjectContext);
            foreach ($metrics as $metric) {
                if (array_key_exists($metric, $value[$subject])) {
                    $limits[$subject . '.' . $metric] = $this->scalarReader->nullablePositiveInt(
                        $value[$subject],
                        $metric,
                        $subjectContext,
                    );
                }
            }
        }

        return $limits;
    }
}
