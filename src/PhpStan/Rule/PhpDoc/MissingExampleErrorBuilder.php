<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\PhpDoc;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;

/**
 * Builds the error reported for a public declaration without an example.
 */
final class MissingExampleErrorBuilder
{
    /**
     * Returns the error for one declaration that declares itself public API.
     *
     * @param string $identifier the PHPStan error identifier of the declaration kind
     * @param string $subject the declaration named as it should read in the message
     * @param int $line the line the declaration starts on
     */
    public function build(string $identifier, string $subject, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(sprintf(
            'Add an @example block to %s: it is declared public API with "@visibility public", so it must document at least one example doctest can run. '
            . 'Write the example as an "@example" tag followed by indented code lines, or as a fenced php block, and assert on it with "// => value", "// Output: text", or "// throws ExceptionClass".',
            $subject,
        ))
            ->identifier($identifier)
            ->line($line)
            ->build();
    }
}
