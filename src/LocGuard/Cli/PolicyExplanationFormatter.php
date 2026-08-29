<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Cli;

use function sprintf;

use Toolkit\LocGuard\Analysis\FilePolicyAssignment;

/**
 * Formats the effective policy selected for one source file.
 */
final class PolicyExplanationFormatter
{
    /**
     * Returns a human-readable policy assignment and all effective limits.
     */
    public function format(FilePolicyAssignment $assignment): string
    {
        $output = sprintf("Path: %s\n", $assignment->relativePath);
        $output .= sprintf("Matched rule: %s\n", $assignment->rule ?? '(default)');
        $output .= sprintf("Policy: %s\n", $assignment->policy->name);
        $output .= sprintf("Extends: %s\n", $assignment->policy->extends ?? '(none)');
        $output .= "\nEffective limits:\n";
        foreach ($assignment->policy->limits->values() as $metric => $limit) {
            $output .= sprintf("  %s: %s\n", $metric, $limit === null ? 'disabled' : (string) $limit);
        }

        return $output;
    }
}
