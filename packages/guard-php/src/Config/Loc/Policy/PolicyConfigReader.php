<?php

declare(strict_types=1);

namespace Guard\Config\Loc\Policy;

use Guard\Config\Loc\ConfigKeyValidator;
use Guard\Config\Loc\ConfigScalarReader;
use Guard\Config\Loc\LimitConfigReader;
use Guard\Policy\PolicyException;

use function is_array;
use function sprintf;

/**
 * Reads one unresolved source metric policy.
 */
final class PolicyConfigReader
{
    /** @readonly */
    private ConfigKeyValidator $keyValidator;

    /** @readonly */
    private ConfigScalarReader $scalarReader;

    /** @readonly */
    private LimitConfigReader $limitConfigReader;

    /**
     * Creates a policy reader from scalar, mapping, and limit validation.
     */
    public function __construct(
        ?ConfigKeyValidator $keyValidator = null,
        ?ConfigScalarReader $scalarReader = null,
        ?LimitConfigReader $limitConfigReader = null,
    ) {
        $this->keyValidator = $keyValidator ?? new ConfigKeyValidator();
        $this->scalarReader = $scalarReader ?? new ConfigScalarReader();
        $this->limitConfigReader = $limitConfigReader ?? new LimitConfigReader();
    }

    /**
     * Reads one policy block.
     *
     * @param mixed $value
     *
     * @throws PolicyException when the policy block is invalid
     */
    public function read(string $name, $value): PolicyDefinition
    {
        $context = sprintf('policies.%s', $name);
        if (!is_array($value)) {
            throw new PolicyException(sprintf('Invalid loc.yaml: "%s" must be a mapping.', $context));
        }

        $this->keyValidator->rejectUnknown($value, ['extends', 'limits'], $context);

        return new PolicyDefinition(
            $name,
            $this->scalarReader->optionalString($value, 'extends', $context),
            $this->limitConfigReader->read($value['limits'] ?? [], $context . '.limits'),
        );
    }
}
