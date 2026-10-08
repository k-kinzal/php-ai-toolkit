<?php

declare(strict_types=1);

namespace Guard\Reporting;

use Guard\Diagnostic\Finding;
use Guard\Policy\FileChange;
use Guard\Reporting\Filtering\ReportScope;
use JsonException;
use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * Renders human diagnostics, compact AI diagnostics and structured reports.
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
    public function render(array $findings, array $changes, string $format, string $action, bool $decorated = false, ?ReportScope $scope = null): string
    {
        $rows = [];
        foreach ($findings as $finding) {
            $rows[] = ['path' => $finding->path, 'rule' => $finding->rule, 'level' => $finding->level === 'required' ? 'error' : 'warning',
                'message' => $finding->message, 'fixable' => $finding->fixable];
        }
        $diffs = [];
        foreach ($changes as $change) {
            $diffs[] = ['path' => $change->path, 'diff' => (new ChangeDiff())->render($change)];
        }
        if ($format === 'json') {
            return json_encode(['findings' => $rows, 'changes' => array_column($diffs, 'path'), 'diffs' => $diffs,
                'action' => $action, 'success' => !$this->hasErrors($scope === null ? $findings : $scope->findings), 'summary' => $this->counts($findings)] + ($scope === null ? [] : [
                    'selection' => ['shown' => count($findings), 'total' => count($scope->findings), 'hidden' => count($scope->findings) - count($findings)],
                    'totalSummary' => $this->counts($scope->findings),
                    'baseline' => $scope->baseline === null ? null : ['path' => $scope->baseline, 'suppressed' => $scope->suppressed, 'unmatched' => $scope->unmatched],
                ]), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        }
        if ($format === 'ai') {
            return $this->ai($findings, $changes, $action, $scope);
        }
        return $this->human($findings, $changes, $action, $decorated, $scope);
    }

    /**
     * @param list<Finding> $findings
     * @return array{errors: int, warnings: int, fixable: int}
     */
    public function counts(array $findings): array
    {
        $counts = ['errors' => 0, 'warnings' => 0, 'fixable' => 0];
        foreach ($findings as $finding) {
            ++$counts[$finding->level === 'required' ? 'errors' : 'warnings'];
            if ($finding->fixable) {
                ++$counts['fixable'];
            }
        }
        return $counts;
    }

    /**
     * @param list<Finding> $findings
     * @param list<FileChange> $changes
     */
    public function ai(array $findings, array $changes, string $action, ?ReportScope $scope = null): string
    {
        $lines = [];
        foreach ($findings as $finding) {
            $lines[] = sprintf('%s: %s [%s] fixable=%s %s', $finding->level === 'required' ? 'error' : 'warning', $finding->path, $finding->rule, $finding->fixable ? 'yes' : 'no', $finding->message);
        }
        foreach ($changes as $change) {
            $lines[] = $action . ': ' . $change->path;
            $lines[] = rtrim((new ChangeDiff())->render($change), "\n");
        }
        $lines[] = $this->actionNote($action);
        $lines[] = $this->summary($findings, $changes, $action, ', ') . '.';
        $lines[] = $scope === null ? '' : $scope->note(count($findings));
        $lines[] = $this->hasErrors($scope === null ? $findings : $scope->findings) ? 'Guard found required violations.' : 'Guard passed.';
        return implode("\n", array_filter($lines, static fn (string $line): bool => $line !== '')) . "\n";
    }

    /**
     * @param list<Finding> $findings
     * @param list<FileChange> $changes
     */
    public function human(array $findings, array $changes, string $action, bool $decorated, ?ReportScope $scope = null): string
    {
        $formatter = new OutputFormatter($decorated);
        $title = $action === 'checked' ? 'Guard check' : 'Guard fix';
        $lines = ['', '<options=bold>' . $title . '</>', ''];
        $groups = [];
        foreach ($findings as $finding) {
            $groups[$finding->path][] = $finding;
        }
        foreach ($groups as $group) {
            $lines[] = '<options=bold>' . OutputFormatter::escape($group[0]->path) . '</>';
            foreach ($group as $finding) {
                $level = $finding->level === 'required' ? 'error' : 'warning';
                $color = $finding->level === 'required' ? 'red' : 'yellow';
                $lines[] = sprintf('  <fg=%s>%s:</> %s  <fg=cyan>%s</>', $color, $level, OutputFormatter::escape($finding->rule), $finding->fixable ? 'fixable' : 'manual');
                $lines[] = '    ' . OutputFormatter::escape($finding->message);
            }
            $lines[] = '';
        }
        foreach ($changes as $change) {
            $lines[] = '<options=bold>' . OutputFormatter::escape($action . ': ' . $change->path) . '</>';
            foreach (explode("\n", rtrim((new ChangeDiff())->render($change), "\n")) as $line) {
                $color = str_starts_with($line, '+') ? 'green' : (str_starts_with($line, '-') ? 'red' : 'default');
                $lines[] = '  <fg=' . $color . '>' . OutputFormatter::escape($line) . '</>';
            }
            $lines[] = '';
        }
        $counts = $this->counts($findings);
        $lines[] = '<options=bold>' . $this->summary($findings, $changes, $action, ' · ') . '</>';
        if ($this->actionNote($action) !== '') {
            $lines[] = $this->actionNote($action);
        }
        if ($counts['fixable'] > 0 && $action === 'checked') {
            $lines[] = 'Preview repairs: guard fix --dry-run';
        }
        if ($scope !== null && $scope->note(count($findings)) !== '') {
            $lines[] = OutputFormatter::escape($scope->note(count($findings)));
        }
        $lines[] = $this->hasErrors($scope === null ? $findings : $scope->findings) ? '<fg=red>Guard found required violations.</>' : '<fg=green>Guard passed.</>';
        return $formatter->format(implode("\n", $lines) . "\n") ?? '';
    }

    /**
     * @param list<Finding> $findings
     * @param list<FileChange> $changes
     */
    public function summary(array $findings, array $changes, string $action, string $separator): string
    {
        $counts = $this->counts($findings);
        $files = $action === 'checked' ? count(array_unique(array_map(static fn (Finding $finding): string => $finding->path, $findings))) : count($changes);
        return implode($separator, [
            $counts['errors'] . ($counts['errors'] === 1 ? ' error' : ' errors'),
            $counts['warnings'] . ($counts['warnings'] === 1 ? ' warning' : ' warnings'),
            $counts['fixable'] . ' fixable',
            $files . ($files === 1 ? ' file ' : ' files ') . ($action === 'checked' ? 'with findings' : $action),
        ]);
    }

    /**
     * Explains whether the plan was written, previewed, or blocked.
     */
    public function actionNote(string $action): string
    {
        return match ($action) {
            'blocked' => 'Repairs blocked by required configuration violations. No files were written; diffs show the proposed changes.',
            'would change' => 'Dry run: no files were written. Run guard fix to apply these changes.',
            default => '',
        };
    }
}
