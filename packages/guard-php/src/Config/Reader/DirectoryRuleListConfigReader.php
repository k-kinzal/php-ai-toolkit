<?php

declare(strict_types=1);

namespace Guard\Config\Reader;

use Guard\Config\Value\DirectoryRuleConfig;
use Guard\Policy\PolicyException;

use function is_array;

/**
 * Reads the tree.yaml rules list.
 */
final class DirectoryRuleListConfigReader
{
    /** @readonly */
    private DirectoryRuleConfigReader $ruleConfigReader;

    /**
     * Creates a reader from per-rule validation.
     */
    public function __construct(?DirectoryRuleConfigReader $ruleConfigReader = null)
    {
        $this->ruleConfigReader = $ruleConfigReader ?? new DirectoryRuleConfigReader();
    }

    /**
     * Reads all rule blocks in declaration order.
     *
     * @param mixed $value
     * @return list<DirectoryRuleConfig>
     *
     * @throws PolicyException when the rules section is not a list of mappings
     */
    public function read($value): array
    {
        if (!is_array($value)) {
            throw new PolicyException('Invalid tree.yaml: "rules" must be a list of mappings.');
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
