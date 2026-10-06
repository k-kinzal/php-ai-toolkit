<?php

declare(strict_types=1);

namespace Guard\Policy\Tree;

use Guard\Collect\Subject;
use Guard\Collect\Tree\DirectoryTree;
use Guard\Execution\Context;
use Guard\Execution\Plan;
use Guard\Policy\Policy;
use Guard\Policy\PolicyException;
use Guard\Reporting\Finding;

/**
 * Evaluates every matching directory rule against the same collected tree.
 */
final class TreePolicy implements Policy
{
    /**
     * Overlapping directory rules remain independent.
     * @throws PolicyException
     */
    public function evaluate(Subject $information, Context $context): Plan
    {
        if (!$information instanceof DirectoryTree) {
            throw new PolicyException('TreePolicy requires DirectoryTree. Register it for that information type.');
        }
        $config = $context->configuration->structure;
        $findings = [];
        foreach ($config === null ? [] : $config->rules as $rule) {
            foreach ($information->listings as $listing) {
                if ((new DirectoryPatternMatcher())->matches($rule->path, $listing->relativePath)) {
                    foreach ((new DirectoryRuleInspector())->inspect($rule, $listing, $information->listings) as $violation) {
                        $findings[] = new Finding($violation->path, 'structure.' . $violation->rule, 'required', $violation->message);
                    }
                }
            }
        }
        return new Plan($findings, []);
    }
}
