<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Config\Policy;

use function is_array;
use function sprintf;

use Toolkit\LocGuard\Config\ConfigKeyValidator;
use Toolkit\LocGuard\Config\ConfigScalarReader;
use Toolkit\LocGuard\Config\ConfigStringListReader;
use Toolkit\LocGuard\LocGuardException;

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
     * @throws LocGuardException when the rule is invalid
     */
    public function read($value, int $index): ApplyRuleConfig
    {
        $context = sprintf('apply.rules[%d]', $index);
        if (!is_array($value)) {
            throw new LocGuardException(sprintf('Invalid loc.yaml: "%s" must be a mapping.', $context));
        }
        $this->keyValidator->rejectUnknown($value, ['name', 'match', 'policy'], $context);

        $match = $value['match'] ?? null;
        if (!is_array($match)) {
            throw new LocGuardException(sprintf('Invalid loc.yaml: "%s.match" must be a mapping.', $context));
        }
        $this->keyValidator->rejectUnknown($match, ['paths'], $context . '.match');

        return new ApplyRuleConfig(
            $this->scalarReader->requiredString($value, 'name', $context),
            $this->stringListReader->readRequired($match, 'paths', $context . '.match', false),
            $this->scalarReader->requiredString($value, 'policy', $context),
        );
    }
}
