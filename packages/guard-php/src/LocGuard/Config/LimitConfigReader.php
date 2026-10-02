<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Config;

use function array_key_exists;
use function array_keys;
use function is_array;
use function sprintf;

use Toolkit\LocGuard\LocGuardException;

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
    private ConfigKeyValidator $keyValidator;

    /** @readonly */
    private ConfigScalarReader $scalarReader;

    /**
     * Creates a limit reader from mapping and scalar validation.
     */
    public function __construct(
        ?ConfigKeyValidator $keyValidator = null,
        ?ConfigScalarReader $scalarReader = null,
    ) {
        $this->keyValidator = $keyValidator ?? new ConfigKeyValidator();
        $this->scalarReader = $scalarReader ?? new ConfigScalarReader();
    }

    /**
     * Reads a partial limit mapping while preserving explicit null values.
     *
     * @param mixed $value
     * @return array<string, ?int>
     *
     * @throws LocGuardException when a limit mapping or value is invalid
     */
    public function read($value, string $context = 'limits'): array
    {
        if (!is_array($value)) {
            throw new LocGuardException(sprintf('Invalid loc.yaml: "%s" must be a mapping.', $context));
        }
        $this->keyValidator->rejectUnknown($value, array_keys(self::METRICS), $context);

        $limits = [];
        foreach (self::METRICS as $subject => $metrics) {
            if (!array_key_exists($subject, $value)) {
                continue;
            }
            if (!is_array($value[$subject])) {
                throw new LocGuardException(sprintf(
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
