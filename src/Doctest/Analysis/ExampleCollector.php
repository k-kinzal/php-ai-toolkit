<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Analysis;

use function explode;
use function ltrim;
use function preg_replace;
use function str_contains;
use function trim;

/**
 * Turns the documented declarations of a file into located examples.
 *
 * Each example keeps the line its first statement sits on in the source file,
 * so a failure report points at the docblock rather than at the declaration
 * the docblock happens to precede.
 */
final class ExampleCollector
{
    /** @readonly */
    private SourceScanner $scanner;

    /** @readonly */
    private DoctestExtractor $extractor;

    /**
     * Creates a collector from source scanning and example extraction.
     */
    public function __construct(?SourceScanner $scanner = null, ?DoctestExtractor $extractor = null)
    {
        $this->scanner = $scanner ?? new SourceScanner();
        $this->extractor = $extractor ?? new DoctestExtractor();
    }

    /**
     * Returns every example of one source file, in docblock order.
     *
     * @return list<Example>
     *
     * @param string $path the readable path of the file
     * @param string|null $displayPath the path reports name the file by
     *
     * @throws \PhpAiToolkit\Doctest\DoctestException when the file cannot be read or parsed
     */
    public function collect(string $path, ?string $displayPath = null): array
    {
        $examples = [];
        foreach ($this->scanner->scan($path, $displayPath) as $target) {
            foreach ($this->extractor->extract($target->docComment) as $docExample) {
                $examples[] = new Example($target, $docExample, $this->line($target, $docExample));
            }
        }

        return $examples;
    }

    /**
     * Returns the source line the example body starts on.
     *
     * The docblock is searched for the first code line of the example, which
     * locates it without the extractor having to carry byte offsets that the
     * two supported docblock shapes report differently.
     */
    public function line(Target $target, DocExample $example): int
    {
        $needle = $this->firstCodeLine($example->code);
        if ($needle === '') {
            return $target->line;
        }

        foreach (explode("\n", $target->docComment) as $offset => $line) {
            if (str_contains($this->stripFrame($line), $needle)) {
                return $target->line + $offset;
            }
        }

        return $target->line;
    }

    /**
     * Returns the first non-empty line of example code.
     */
    public function firstCodeLine(string $code): string
    {
        foreach (explode("\n", $code) as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return '';
    }

    /**
     * Strips the leading asterisk frame from one raw docblock line.
     */
    public function stripFrame(string $line): string
    {
        $stripped = preg_replace('/^\s*(?:\/\*\*)?\s*\*?\s?/', '', $line);

        return trim(ltrim($stripped ?? $line));
    }
}
