<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Analysis;

use function preg_quote;
use function sprintf;

/**
 * Builds the PHPUnit filter that selects one documented example.
 *
 * An example identifier reads well but is not a regular expression: it carries
 * backslashes, parentheses, and a hash. PHPUnit filters are regular
 * expressions, so the identifier is quoted before it is handed over, and the
 * result selects that one example and nothing else.
 *
 * @visibility public
 *
 * @example Selecting one example from the command line
 *     $filter = new PhpUnitFilter();
 *     $filter->pattern('App\\\\Ledger::append()#2') // => '/App\\\\\\\\Ledger\\:\\:append\\(\\)\\#2/'
 */
final class PhpUnitFilter
{
    /**
     * Returns the filter pattern that matches exactly one example.
     */
    public function pattern(string $id): string
    {
        return sprintf('/%s/', preg_quote($id, '/'));
    }

    /**
     * Returns the whole command that runs one example on its own.
     */
    public function command(string $id): string
    {
        return sprintf("vendor/bin/phpunit --filter '%s'", $this->pattern($id));
    }
}
