<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Execution;

use function is_array;

use PhpAiToolkit\Doctest\Analysis\AssertionScanner;

use function rtrim;
use function str_ends_with;

use const T_CURLY_OPEN;

use function token_get_all;

/**
 * Groups the lines of an example into executable statements.
 *
 * A statement ends where the source is balanced and no operator is left
 * dangling, so a call spread over several lines runs as one statement and the
 * assertion written on its closing line applies to the whole call.
 */
final class StatementBuilder
{
    /** @var list<string> */
    private const CONTINUATIONS = ['.', ',', '=>', '->', '?->', '::', '&&', '||', '??', '?', ':', '=', '+', '-', '*', '/', '%', '<', '>', '!', '|', '&'];

    /** @readonly */
    private AssertionScanner $scanner;

    /** @readonly */
    private SourceSyntax $syntax;

    /**
     * Creates a builder from assertion line scanning and the source syntax check.
     */
    public function __construct(?AssertionScanner $scanner = null, ?SourceSyntax $syntax = null)
    {
        $this->scanner = $scanner ?? new AssertionScanner();
        $this->syntax = $syntax ?? new SourceSyntax();
    }

    /**
     * Splits example code into the statements doctest executes.
     *
     * @return list<Statement>
     *
     * @throws \PhpAiToolkit\Doctest\DoctestException when no parser can be created
     */
    public function build(string $code): array
    {
        $statements = [];
        $buffer = '';
        $bufferLine = 1;

        foreach ($this->scanner->scan($code) as $offset => $line) {
            if ($line->code === '' && $line->marker === null) {
                continue;
            }

            if ($buffer === '') {
                $bufferLine = $offset + 1;
            }

            $buffer = $buffer === '' ? $line->code : $buffer . ' ' . $line->code;
            if ($line->marker === null && !$this->complete($buffer)) {
                continue;
            }

            $statements[] = new Statement($buffer, $line->marker, $line->expected, $line->exceptionMessage, $bufferLine);
            $buffer = '';
        }

        if ($buffer !== '') {
            $statements[] = new Statement($buffer, null, null, null, $bufferLine);
        }

        return $statements;
    }

    /**
     * Reports whether the buffered source is a finished statement.
     *
     * @throws \PhpAiToolkit\Doctest\DoctestException when no parser can be created
     */
    public function complete(string $code): bool
    {
        if ($this->depth($code) > 0 || $this->dangling($code)) {
            return false;
        }

        return $this->syntax->parses($code);
    }

    /**
     * Reports whether the source ends on an operator that needs a right-hand side.
     */
    public function dangling(string $code): bool
    {
        $trimmed = rtrim($code);
        foreach (self::CONTINUATIONS as $operator) {
            if (str_ends_with($trimmed, $operator)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns how many brackets the source leaves open.
     */
    public function depth(string $code): int
    {
        $depth = 0;
        foreach (token_get_all('<?php ' . $code) as $token) {
            if (is_array($token)) {
                $depth += $token[0] === T_CURLY_OPEN ? 1 : 0;
                continue;
            }

            $depth += $token === '(' || $token === '[' || $token === '{' ? 1 : 0;
            $depth -= $token === ')' || $token === ']' || $token === '}' ? 1 : 0;
        }

        return $depth;
    }
}
