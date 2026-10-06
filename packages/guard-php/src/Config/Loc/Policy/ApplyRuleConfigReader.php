<?php

declare(strict_types=1);

namespace Guard\Config\Loc\Policy;

use Guard\Config\Loc\ConfigKeyValidator;
use Guard\Config\Loc\ConfigScalarReader;
use Guard\Config\Loc\ConfigStringListReader;
use Guard\Policy\PolicyException;

use function is_array;
use function sprintf;

/**
 * Reads one policy application rule.
 */
final class ApplyRuleConfigReader
{
    /** @readonly */
    private ConfigKeyValidator $keyValidator;

    /** @readonly */
    private ConfigScalarReader $scalarReader;

    /** @readonly */
    private ConfigStringListReader $stringListReader;

    /**
     * Creates a rule reader from scalar, list, and mapping validation.
     */
    public function __construct(
        ?ConfigKeyValidator $keyValidator = null,
        ?ConfigScalarReader $scalarReader = null,
        ?ConfigStringListReader $stringListReader = null,
    ) {
        $this->keyValidator = $keyValidator ?? new ConfigKeyValidator();
        $this->scalarReader = $scalarReader ?? new ConfigScalarReader();
        $this->stringListReader = $stringListReader ?? new ConfigStringListReader();
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
