<?php

declare(strict_types=1);

namespace Guard\Reporting;

use Guard\Config\Schema;
use Guard\Policy\PolicyException;
use JsonException;

/**
 * Records exact, counted diagnostics so new occurrences and changed violations remain visible.
 */
final class Baseline
{
    /**
     * Builds a deterministic, reviewable baseline without timestamps or absolute project roots.
     * @param list<Finding> $findings
     * @throws JsonException
     */
    public function capture(array $findings): string
    {
        $entries = [];
        foreach ($findings as $finding) {
            $entry = ['path' => $finding->path, 'rule' => $finding->rule,
                'level' => $finding->level === 'required' ? 'error' : 'warning', 'message' => $finding->message];
            $key = $this->key($entry);
            if (!isset($entries[$key])) {
                $entries[$key] = $entry + ['count' => 0];
            }
            ++$entries[$key]['count'];
        }
        ksort($entries);
        return json_encode(['version' => 1, 'entries' => array_values($entries)], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    /**
     * Validates every entry, including unused entries, before any suppression.
     * @return list<array{path: string, rule: string, level: string, message: string, count: int}>
     * @throws JsonException
     * @throws PolicyException
     */
    public function entries(string $json): array
    {
        $schema = new Schema();
        $data = $schema->mapping(json_decode($json, true, 512, JSON_THROW_ON_ERROR), ['version', 'entries'], 'baseline');
        if (($data['version'] ?? null) !== 1 || !isset($data['entries']) || !is_array($data['entries']) || array_values($data['entries']) !== $data['entries']) {
            throw new PolicyException('Invalid baseline. Generate a version 1 baseline with guard baseline.');
        }
        $entries = [];
        $seen = [];
        foreach ($data['entries'] as $value) {
            $entry = $this->entry($value);
            $key = $this->key($entry);
            if (isset($seen[$key])) {
                throw new PolicyException('Duplicate baseline entry for ' . $entry['path'] . ' [' . $entry['rule'] . ']. Combine its counts or regenerate with guard baseline.');
            }
            $seen[$key] = true;
            $entries[] = $entry;
        }
        return $entries;
    }

    /**
     * @param mixed $value
     * @return array{path: string, rule: string, level: string, message: string, count: int}
     * @throws PolicyException
     */
    public function entry(mixed $value): array
    {
        $schema = new Schema();
        $data = $schema->mapping($value, ['path', 'rule', 'level', 'message', 'count'], 'baseline entry');
        $result = [];
        foreach (['path', 'rule', 'level', 'message'] as $key) {
            $result[$key] = $schema->string($data[$key] ?? null, 'baseline entry.' . $key);
        }
        if (!in_array($result['level'], ['error', 'warning'], true) || !is_int($data['count'] ?? null) || $data['count'] < 1) {
            throw new PolicyException('Baseline entries require level error or warning and a positive integer count. Regenerate with guard baseline.');
        }
        return ['path' => $result['path'], 'rule' => $result['rule'], 'level' => $result['level'], 'message' => $result['message'], 'count' => $data['count']];
    }

    /**
     * Matches exact messages, including metric values, without ignoring additional occurrences.
     * @param list<Finding> $findings
     * @throws JsonException
     * @throws PolicyException
     */
    public function filter(array $findings, string $json): BaselineMatch
    {
        $remaining = [];
        foreach ($this->entries($json) as $entry) {
            $remaining[$this->key($entry)] = $entry['count'];
        }
        $active = [];
        $suppressed = 0;
        foreach ($findings as $finding) {
            $key = $this->key(['path' => $finding->path, 'rule' => $finding->rule,
                'level' => $finding->level === 'required' ? 'error' : 'warning', 'message' => $finding->message]);
            if (($remaining[$key] ?? 0) > 0) {
                --$remaining[$key];
                ++$suppressed;
            } else {
                $active[] = $finding;
            }
        }
        return new BaselineMatch($active, $suppressed, array_sum($remaining));
    }

    /**
     * Uses structured encoding to avoid collisions from separators inside messages or paths.
     * @param array{path: string, rule: string, level: string, message: string} $entry
     * @throws JsonException
     */
    public function key(array $entry): string
    {
        return json_encode([$entry['path'], $entry['rule'], $entry['level'], $entry['message']], JSON_THROW_ON_ERROR);
    }
}
