<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Config;

use function is_array;

use PhpAiToolkit\TreeGuard\TreeGuardException;

/**
 * Reads the tree.yaml rules list.
 */
final class RuleListConfigReader
{
    /** @readonly */
    private RuleConfigReader $ruleConfigReader;

    /**
     * Creates a reader from per-rule validation.
     */
    public function __construct(?RuleConfigReader $ruleConfigReader = null)
    {
        $this->ruleConfigReader = $ruleConfigReader ?? new RuleConfigReader();
    }

    /**
     * Reads all rule blocks in declaration order.
     *
     * @param mixed $value
     * @return list<RuleConfig>
     *
     * @throws TreeGuardException when the rules section is not a list of mappings
     */
    public function read($value): array
    {
        if (!is_array($value)) {
            throw new TreeGuardException('Invalid tree.yaml: "rules" must be a list of mappings.');
        }

        $rules = [];
        $index = 0;
        foreach ($value as $entry) {
            $rules[] = $this->ruleConfigReader->read($entry, $index);
            $index++;
        }

        return $rules;
    }
}
