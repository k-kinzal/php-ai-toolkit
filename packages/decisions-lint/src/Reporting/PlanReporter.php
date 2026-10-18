<?php

declare(strict_types=1);

namespace DecisionsLint\Reporting;

use DecisionsLint\Lint\Plan;
use JsonException;

/**
 * Shows what a run would ask without sending anything: each judged declaration and the rules that judge it.
 */
final class PlanReporter
{
    /**
     * Returns the plan in the requested format; text for every format but json.
     *
     * @throws JsonException when a target holds text that cannot be encoded
     */
    public function render(Plan $plan, string $format): string
    {
        $groups = [];
        foreach ($plan->assignments as $assignment) {
            $target = $assignment->target;
            $key = $assignment->inquiry;
            $groups[$key] ??= ['file' => $target->file, 'line' => $target->line, 'column' => $target->column, 'kind' => $target->kind, 'symbol' => $target->qualifiedName, 'context' => $assignment->rule->context, 'rules' => []];
            $groups[$key]['rules'][] = $assignment->rule->id;
        }
        $groups = array_values($groups);
        if ($format === 'json') {
            return json_encode(['requests' => $groups, 'files' => count($plan->files), 'targets' => $plan->targets(), 'questions' => count($plan->assignments)], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        }
        $lines = [];
        foreach ($groups as $group) {
            $lines[] = sprintf('%s:%d:%d %s %s (%s) <- %s', $group['file'], $group['line'], $group['column'], $group['kind'], $group['symbol'], $group['context'], implode(', ', $group['rules']));
        }
        $lines[] = sprintf('%d files, %d targets, %d requests, %d questions. Nothing was sent.', count($plan->files), $plan->targets(), count($groups), count($plan->assignments));

        return implode("\n", $lines) . "\n";
    }
}
