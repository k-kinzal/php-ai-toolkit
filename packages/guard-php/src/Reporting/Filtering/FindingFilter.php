<?php

declare(strict_types=1);

namespace Guard\Reporting\Filtering;

use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;

/**
 * Selects diagnostics for display without changing the result of the check.
 */
final class FindingFilter
{
    /**
     * Validates the user's display filters before inspecting project files.
     * @throws PolicyException
     */
    public function __construct(private string $query = '', private ?string $level = null, private bool $fixable = false)
    {
        if ($level !== null && !in_array($level, ['error', 'warning'], true)) {
            throw new PolicyException('Unsupported --level "' . $level . '". Use --level=error or --level=warning.');
        }
    }

    /**
     * Combines the filters with AND; query is a literal, case-insensitive substring.
     * @param list<Finding> $findings
     * @return list<Finding>
     */
    public function select(array $findings): array
    {
        return array_values(array_filter($findings, function (Finding $finding): bool {
            $level = $finding->level === 'required' ? 'error' : 'warning';
            $text = implode(' ', [$finding->path, $finding->rule, $finding->message, $level, $finding->fixable ? 'fixable' : 'manual']);
            return (!$this->fixable || $finding->fixable)
                && ($this->level === null || $this->level === $level)
                && str_contains(strtolower($text), strtolower($this->query));
        }));
    }
}
