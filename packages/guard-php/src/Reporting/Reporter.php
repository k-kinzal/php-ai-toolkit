<?php

declare(strict_types=1);

namespace Guard\Reporting;

use Guard\Execution\FileChange;
use JsonException;

/**
 * Renders required failures and non-fatal recommendations consistently.
 */
final class Reporter
{
    /**
     * @param list<Finding> $findings
     */
    public function hasErrors(array $findings): bool
    {
        foreach ($findings as $finding) {
            if ($finding->level === 'required') {
                return true;
            }
        }
        return false;
    }
    /**
     * @param list<Finding> $findings
     * @param list<FileChange> $changes
     * @throws JsonException
     */
    public function render(array $findings, array $changes, string $format, string $action): string
    {
        $rows = [];
        $lines = [];
        foreach ($findings as $finding) {
            $level = $finding->level === 'required' ? 'error' : 'warning';
            $rows[] = ['path' => $finding->path, 'rule' => $finding->rule, 'level' => $level, 'message' => $finding->message];
            $lines[] = $level . ': ' . $finding->path . ' [' . $finding->rule . '] ' . $finding->message;
        }
        $paths = [];
        foreach ($changes as $change) {
            $paths[] = $change->path;
            $lines[] = $action . ': ' . $change->path;
        }
        if ($format === 'json') {
            return json_encode(['findings' => $rows, 'changes' => $paths, 'action' => $action, 'success' => !$this->hasErrors($findings)], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        }
        $lines[] = $this->hasErrors($findings) ? 'Guard found required violations.' : 'Guard passed.';
        return implode("\n", $lines) . "\n";
    }
}
