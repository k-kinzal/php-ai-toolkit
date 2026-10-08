<?php

declare(strict_types=1);

namespace Guard\Reporting;

use JsonException;
use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * Presents searchable rule descriptions without checking target files.
 */
final class RuleReporter
{
    /**
     * @param list<RuleDescription> $rules
     * @return list<RuleDescription>
     */
    public function filter(array $rules, string $query): array
    {
        if ($query === '') {
            return $rules;
        }
        $needle = strtolower($query);
        return array_values(array_filter($rules, static fn (RuleDescription $rule): bool => str_contains(strtolower(
            $rule->id . ' ' . $rule->target . ' ' . $rule->level . ' ' . $rule->message . ' ' . ($rule->fixable ? 'fixable' : 'manual')
        ), $needle)));
    }

    /**
     * @param list<RuleDescription> $rules
     * @throws JsonException
     */
    public function render(array $rules, string $format, bool $decorated = false): string
    {
        if ($format === 'json') {
            return json_encode(['rules' => $this->rows($rules), 'count' => count($rules)], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        }
        $lines = [];
        foreach ($rules as $rule) {
            $details = json_encode($rule->details, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $label = sprintf('%s  %s  %s', $rule->id, $rule->level, $rule->fixable ? 'fixable' : 'manual');
            if ($format === 'ai') {
                $lines[] = $label . ' target=' . $rule->target;
                $lines[] = '  message: ' . $rule->message;
                $lines[] = '  constraints: ' . $details;
                continue;
            }
            $lines[] = '<options=bold>' . OutputFormatter::escape($rule->id) . '</>  <fg=cyan>' . $rule->level . ' · ' . ($rule->fixable ? 'fixable' : 'manual') . '</>';
            $lines[] = '  Target: ' . OutputFormatter::escape($rule->target);
            $lines[] = '  Message: ' . OutputFormatter::escape($rule->message);
            $lines[] = '  Constraints: ' . OutputFormatter::escape($details);
            $lines[] = '';
        }
        $lines[] = count($rules) . (count($rules) === 1 ? ' active rule' : ' active rules') . ($rules === [] ? ' (no matches).' : '.');
        $text = implode("\n", $lines) . "\n";
        return $format === 'ai' ? $text : ((new OutputFormatter($decorated))->format($text) ?? '');
    }
    /**
     * @param list<RuleDescription> $rules
     * @return list<array<string, mixed>>
     */
    public function rows(array $rules): array
    {
        $rows = [];
        foreach ($rules as $rule) {
            $rows[] = $rule->toArray();
        }
        return $rows;
    }
}
