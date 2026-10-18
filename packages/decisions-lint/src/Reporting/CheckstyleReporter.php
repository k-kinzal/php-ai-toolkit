<?php

declare(strict_types=1);

namespace DecisionsLint\Reporting;

use DecisionsLint\Lint\Report;

/**
 * Renders the report as Checkstyle XML, which editors and CI systems already read.
 */
final class CheckstyleReporter implements Reporter
{
    /**
     * Returns a `checkstyle` document with one `file` element per path and one `error` element per finding.
     */
    public function render(Report $report, bool $decorated): string
    {
        $groups = [];
        foreach ($report->findings as $finding) {
            $groups[$finding->path][] = $finding;
        }
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>', '<checkstyle version="decisions-lint">'];
        foreach ($groups as $path => $findings) {
            $lines[] = '  <file name="' . $this->escape($path) . '">';
            foreach ($findings as $finding) {
                $lines[] = sprintf('    <error line="%d" column="%d" severity="%s" message="%s" source="%s"/>', $finding->line, $finding->column, $finding->severity, $this->escape($finding->message . ' Fix: ' . $finding->fix), $this->escape($finding->rule));
            }
            $lines[] = '  </file>';
        }
        $lines[] = '</checkstyle>';

        return implode("\n", $lines) . "\n";
    }

    /**
     * Escapes text for an XML attribute.
     */
    public function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
