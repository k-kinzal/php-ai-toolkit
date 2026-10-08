<?php

declare(strict_types=1);

namespace Guard\Config\Profile;

use Guard\Config\Validation\MetricConfigKeyValidator;
use Guard\Config\Validation\MetricConfigScalarReader;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\Definition\ApplyConfig;
use Guard\Policy\Definition\PolicyConfig;

use function is_array;

/**
 * Reads and validates policy application configuration.
 */
final class ApplyConfigReader
{
    /** @readonly */
    private MetricConfigKeyValidator $keyValidator;

    /** @readonly */
    private MetricConfigScalarReader $scalarReader;

    /** @readonly */
    private ApplyRuleListConfigReader $ruleListConfigReader;

    /** @readonly */
    private ApplyPolicyUsageValidator $policyUsageValidator;

    /**
     * Creates an apply reader from mapping, scalar, and rule validation.
     */
    public function __construct(
        ?MetricConfigKeyValidator $keyValidator = null,
        ?MetricConfigScalarReader $scalarReader = null,
        ?ApplyRuleListConfigReader $ruleListConfigReader = null,
        ?ApplyPolicyUsageValidator $policyUsageValidator = null,
    ) {
        $this->keyValidator = $keyValidator ?? new MetricConfigKeyValidator();
        $this->scalarReader = $scalarReader ?? new MetricConfigScalarReader();
        $this->ruleListConfigReader = $ruleListConfigReader ?? new ApplyRuleListConfigReader();
        $this->policyUsageValidator = $policyUsageValidator ?? new ApplyPolicyUsageValidator();
    }

    /**
     * Reads default and path-specific policy assignments.
     *
     * @param mixed $value
     * @param array<string, PolicyConfig> $policies
     *
     * @throws PolicyException when assignments are invalid
     */
    public function read($value, array $policies): ApplyConfig
    {
        if (!is_array($value)) {
            throw new PolicyException('Invalid loc.yaml: "apply" must be a mapping.');
        }
        $this->keyValidator->rejectUnknown($value, ['default', 'rules'], 'apply');

        $defaultPolicy = $this->scalarReader->requiredString($value, 'default', 'apply');
        $rules = $this->ruleListConfigReader->read($value['rules'] ?? []);
        $this->policyUsageValidator->validate($defaultPolicy, $rules, $policies);

        return new ApplyConfig($defaultPolicy, $rules);
    }
}
