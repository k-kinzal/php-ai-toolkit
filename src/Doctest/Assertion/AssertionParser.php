<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Assertion;

use function explode;

use PhpAiToolkit\Doctest\Parser\Example;

use function preg_match;
use function trim;

/**
 * Parses example code to extract statements and assertions.
 *
 * The parser recognizes several assertion formats:
 * - Return value: `expression // => expected_value`
 * - Output: `echo $var; // Output: expected_output`
 * - Exception: `throw new Exception(); // throws Exception`
 * - Exception with message: `throw new Exception("msg"); // throws Exception: msg`
 *
 * Lines without assertions become "smoke test" statements that just verify
 * the code runs without errors.
 */
final class AssertionParser
{
    /** @var list<string> */
    private const INCOMPLETE_PATTERNS = [
        '/\.\s*$/',
        '/,\s*$/',
        '/\(\s*$/',
        '/\[\s*$/',
        '/\{\s*$/',
        '/=>\s*$/',
        '/\->\s*$/',
        '/::\s*$/',
        '/&&\s*$/',
        '/\|\|\s*$/',
        '/\?\s*$/',
    ];

    /**
     * Parse example code and extract assertions.
     *
     * Supported formats:
     * - `expression // => expected_value`
     * - `echo $var; // Output: expected_output`
     * - `throw new Exception(); // throws Exception`
     * - `throw new Exception("msg"); // throws Exception: msg`
     *
     * @param Example $example the example to parse
     * @return ParsedExample the parsed example with statements
     */
    public function parse(Example $example): ParsedExample
    {
        $lines = explode("\n", $example->code);
        $statements = [];
        $currentCode = '';
        $lineNum = 0;

        foreach ($lines as $index => $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            $lineNum = $index + 1;
            $statement = $this->parseLine($trimmed, $currentCode, $lineNum);
            if ($statement !== null) {
                $statements[] = $statement;
                $currentCode = '';
                continue;
            }

            if ($this->isIncompleteLine($trimmed)) {
                $currentCode .= $trimmed . ' ';
                continue;
            }

            $statements[] = new Statement($currentCode . $trimmed, null, $lineNum);
            $currentCode = '';
        }

        if ($currentCode !== '') {
            $statements[] = new Statement(trim($currentCode), null, $lineNum);
        }

        return new ParsedExample($example, $statements);
    }

    /**
     * Parses one line into an asserted statement, or null when it carries no assertion.
     *
     * @param string $trimmed the trimmed source line
     * @param string $currentCode code buffered from earlier incomplete lines
     * @param int $lineNum line number within the example
     */
    public function parseLine(string $trimmed, string $currentCode, int $lineNum): ?Statement
    {
        $matches = [];
        if (preg_match('/^(.+?)\s*\/\/\s*=>\s*(.+)$/', $trimmed, $matches) === 1) {
            return new Statement($currentCode . trim($matches[1]), new Assertion(AssertionKind::RETURN_VALUE, trim($matches[2])), $lineNum);
        }

        if (preg_match('/^(.+?)\s*\/\/\s*Output:\s*(.*)$/', $trimmed, $matches) === 1) {
            return new Statement($currentCode . trim($matches[1]), new Assertion(AssertionKind::OUTPUT, $matches[2]), $lineNum);
        }

        if (preg_match('/^(.+?)\s*\/\/\s*throws\s+(\S+?)(?::\s*(.*))?$/', $trimmed, $matches) === 1) {
            $message = isset($matches[3]) ? trim($matches[3]) : null;

            return new Statement($currentCode . trim($matches[1]), new Assertion(AssertionKind::EXCEPTION, $matches[2], $message), $lineNum);
        }

        return null;
    }

    /**
     * Reports whether the line is likely continued on the next one.
     *
     * A line ending in an operator or an opening bracket has no complete
     * meaning on its own, so it is buffered until the statement finishes.
     */
    public function isIncompleteLine(string $line): bool
    {
        foreach (self::INCOMPLETE_PATTERNS as $pattern) {
            if (preg_match($pattern, $line) === 1) {
                return true;
            }
        }

        return false;
    }
}
