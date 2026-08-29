<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Config\Policy;

use function array_values;
use function is_array;
use function sprintf;

use Toolkit\LocGuard\LocGuardException;

/**
 * Reads and validates the ordered list of policy application rules.
 */
final class ApplyRuleListConfigReader
{
    /** @readonly */
    private ApplyRuleConfigReader $ruleConfigReader;

    /**
     * Creates a rule-list reader from single-rule validation.
     */
    public function __construct(?ApplyRuleConfigReader $ruleConfigReader = null)
    {
        $this->ruleConfigReader = $ruleConfigReader ?? new ApplyRuleConfigReader();
    }

    /**
     * @param mixed $value
     * @return list<ApplyRuleConfig>
     *
     * @throws LocGuardException when the rules are not a list or contain duplicate names
     */
    public function read($value): array
    {
        if (!is_array($value) || array_values($value) !== $value) {
            throw new LocGuardException('Invalid loc.yaml: "apply.rules" must be a list of mappings.');
        }

        $rules = [];
        $ruleNames = [];
        foreach ($value as $index => $ruleValue) {
            $rule = $this->ruleConfigReader->read($ruleValue, $index);
            if (isset($ruleNames[$rule->name])) {
                throw new LocGuardException(sprintf('Invalid loc.yaml: duplicate apply rule name "%s".', $rule->name));
            }
            $ruleNames[$rule->name] = true;
            $rules[] = $rule;
        }

        return $rules;
    }
}
