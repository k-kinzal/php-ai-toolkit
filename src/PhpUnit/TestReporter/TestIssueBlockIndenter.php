<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

use function explode;
use function sprintf;
use function trim;

/**
 * Indents multi-line blocks embedded in issue output.
 */
final class TestIssueBlockIndenter
{
    /**
     * Prefixes each trimmed block line with two spaces.
     */
    public function indent(string $block): string
    {
        $output = '';
        $lines = explode("\n", trim($block));
        foreach ($lines as $line) {
            $output .= sprintf("  %s\n", $line);
        }

        return $output;
    }
}
