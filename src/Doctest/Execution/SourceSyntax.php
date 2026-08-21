<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Execution;

use function array_values;

use PhpAiToolkit\Doctest\Analysis\PhpParserBridge;
use PhpAiToolkit\Doctest\DoctestException;
use PhpParser\ErrorHandler\Collecting;

/**
 * Answers syntax questions about a fragment of example source.
 *
 * Whether a fragment is a finished statement is decided by parsing it rather
 * than by matching how its last line ends, so a declaration spread over several
 * lines is held together the same way a call spread over several lines is.
 */
final class SourceSyntax
{
    /** @readonly */
    private PhpParserBridge $bridge;

    /**
     * Creates the syntax check from the php-parser version bridge.
     */
    public function __construct(?PhpParserBridge $bridge = null)
    {
        $this->bridge = $bridge ?? new PhpParserBridge();
    }

    /**
     * Returns the statements the fragment parses into, or null when it does not parse.
     *
     * @return list<\PhpParser\Node\Stmt>|null
     *
     * @throws DoctestException when no parser can be created
     */
    public function parse(string $code): ?array
    {
        $errorHandler = new Collecting();
        $statements = $this->bridge->parser()->parse('<?php ' . $code . ';', $errorHandler);
        if ($errorHandler->hasErrors() || $statements === null) {
            return null;
        }

        return array_values($statements);
    }

    /**
     * Reports whether the fragment is syntactically complete on its own.
     *
     * @throws DoctestException when no parser can be created
     */
    public function parses(string $code): bool
    {
        return $this->parse($code) !== null;
    }
}
