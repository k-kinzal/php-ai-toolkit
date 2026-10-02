<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Generation;

use function implode;
use function str_replace;

use Toolkit\DocGuard\Markdown\Heading;

/**
 * Writes a doc-guard.yaml document that declares the given headings as the fixed structure.
 */
final class ConfigYamlWriter
{
    /**
     * Returns the YAML for documents keyed by path and the scan patterns.
     *
     * PHP turns a numeric path such as "2024" into an integer key, so keys are cast back to strings.
     *
     * @param array<array-key, list<Heading>> $documents
     * @param list<string> $scan
     */
    public function write(array $documents, array $scan): string
    {
        $lines = [
            '# NOTE: You do not have permission to overwrite this file. Please ask a human operator to perform the changes for you.',
            'documents:',
        ];
        foreach ($documents as $path => $headings) {
            $lines[] = '  ' . $this->quote((string) $path) . ':';
            $lines[] = $headings === [] ? '    headings: []' : '    headings:';
            foreach ($headings as $heading) {
                $lines[] = '      - ' . $this->quote($heading->notation());
            }
        }

        $lines[] = '';
        $lines[] = $scan === [] ? 'scan: []' : 'scan:';
        foreach ($scan as $pattern) {
            $lines[] = '  - ' . $this->quote($pattern);
        }

        $lines[] = '';
        $lines[] = 'report:';
        $lines[] = '  reporter: ai';
        $lines[] = '  order_by:';
        $lines[] = '    - path';
        $lines[] = '    - line';
        $lines[] = '    - rule';

        return implode("\n", $lines) . "\n";
    }

    /**
     * Returns a value as a YAML single-quoted scalar, which keeps "#" and ":" literal.
     */
    public function quote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}
