<?php

declare(strict_types=1);

namespace Guard\Config\Profile;

use Guard\Config\Validation\MetricConfigKeyValidator;
use Guard\Config\Validation\MetricConfigScalarReader;
use Guard\Config\Validation\MetricConfigStringListReader;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\Definition\ApplyRuleConfig;

use function is_array;
use function sprintf;

/**
 * Reads one policy application rule.
 */
final class ApplyRuleConfigReader
{
    /** @readonly */
    private MetricConfigKeyValidator $keyValidator;

    /** @readonly */
    private MetricConfigScalarReader $scalarReader;

    /** @readonly */
    private MetricConfigStringListReader $stringListReader;

    /**
     * Creates a rule reader from scalar, list, and mapping validation.
     */
    public function __construct(
        ?MetricConfigKeyValidator $keyValidator = null,
        ?MetricConfigScalarReader $scalarReader = null,
        ?MetricConfigStringListReader $stringListReader = null,
    ) {
        $this->keyValidator = $keyValidator ?? new MetricConfigKeyValidator();
        $this->scalarReader = $scalarReader ?? new MetricConfigScalarReader();
        $this->stringListReader = $stringListReader ?? new MetricConfigStringListReader();
    }

    /**
     * Reads one indexed application rule.
     *
     * @param mixed $value
     *
     * @throws PolicyException when the rule is invalid
     */
    public function read($value, int $index): ApplyRuleConfig
    {
        $context = sprintf('apply.rules[%d]', $index);
        if (!is_array($value)) {
            throw new PolicyException(sprintf('Invalid loc.yaml: "%s" must be a mapping.', $context));
        }
        $this->keyValidator->rejectUnknown($value, ['name', 'match', 'policy'], $context);

        $match = $value['match'] ?? null;
        if (!is_array($match)) {
            throw new PolicyException(sprintf('Invalid loc.yaml: "%s.match" must be a mapping.', $context));
        }
        $this->keyValidator->rejectUnknown($match, ['paths'], $context . '.match');

        return new ApplyRuleConfig(
            $this->scalarReader->requiredString($value, 'name', $context),
            $this->stringListReader->readRequired($match, 'paths', $context . '.match', false),
            $this->scalarReader->requiredString($value, 'policy', $context),
        );
    }
}
