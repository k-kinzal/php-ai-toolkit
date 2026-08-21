<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use function implode;

use PhpAiToolkit\DocGen\Render\RenderKit;

use function preg_match;
use function sprintf;

/**
 * Renders executable examples with doctest assertion styling.
 *
 * Assertion markers keep their notation in the copyable text, so a copied
 * example stays runnable by doctest-php as-is.
 */
final class ExampleHtml
{
    /**
     * Renders one captioned example figure.
     *
     * Runnable examples carry a doctest badge; display-only examples do not.
     * A runnable example with an identifier also carries the command that runs
     * that one example on its own, so a reader can check a documented claim
     * without running the whole suite.
     *
     * @param string $id the doctest identifier of the example, empty when unknown
     */
    public function figure(RenderKit $services, ?string $description, string $code, bool $runnable, string $id = ''): string
    {
        $escaper = $services->escaper;

        return '<figure class="example">'
            . '<figcaption>'
            . sprintf('<span class="example-title">%s</span>', $escaper->e($description ?? 'Example'))
            . ($runnable ? $this->doctestChip($services, $id) : '')
            . ($runnable && $id !== '' ? $this->runButton($services, $id) : '')
            . '<button class="copy-btn" type="button" title="Copy example">copy</button>'
            . '</figcaption>'
            . $this->codeBlock($services, $code)
            . '</figure>' . "\n";
    }

    /**
     * Renders the badge that marks an example as executable.
     */
    public function doctestChip(RenderKit $services, string $id): string
    {
        $title = $id === '' ? 'Executable with doctest' : sprintf('Executable with doctest as %s', $id);

        return sprintf('<span class="chip chip-sm chip-doctest" title="%s">doctest</span>', $services->escaper->e($title));
    }

    /**
     * Renders the button that copies the command running one example on its own.
     */
    public function runButton(RenderKit $services, string $id): string
    {
        return sprintf(
            '<button class="copy-btn" type="button" data-copy="%s" title="Copy the command that runs this example on its own">run</button>',
            $services->escaper->e($this->runCommand($id)),
        );
    }

    /**
     * Returns the command that runs one example on its own.
     */
    public function runCommand(string $id): string
    {
        return sprintf("vendor/bin/doctest --filter='%s'", $id);
    }

    /**
     * Renders example code with per-line assertion highlighting.
     */
    public function codeBlock(RenderKit $services, string $code): string
    {
        $lines = [];
        foreach ($services->assertions->scan($code) as $line) {
            $indent = '';
            if (preg_match('/^\s+/', $line->text, $match) === 1) {
                $indent = $match[0];
            }

            if ($line->marker === null) {
                $lines[] = $indent . $services->highlighter->highlightSnippet($line->code);
                continue;
            }

            $lines[] = $indent
                . $services->highlighter->highlightSnippet($line->code)
                . ' <span class="doct doct-' . $line->marker . '">' . $services->escaper->e($this->markerText($line->marker, $line->expected ?? '', $line->exceptionMessage)) . '</span>';
        }

        return '<pre class="code-block doctest"><code>' . implode("\n", $lines) . '</code></pre>';
    }

    /**
     * Rebuilds the doctest marker comment of one assertion line.
     */
    public function markerText(string $marker, string $expected, ?string $exceptionMessage): string
    {
        if ($marker === 'return') {
            return '// => ' . $expected;
        }

        if ($marker === 'output') {
            return '// Output: ' . $expected;
        }

        return '// throws ' . $expected . ($exceptionMessage !== null && $exceptionMessage !== '' ? ': ' . $exceptionMessage : '');
    }
}
