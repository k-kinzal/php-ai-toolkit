<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\ExceptionHandling;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;
use function strtolower;

/**
 * Builds the error for a throw that escapes its method undeclared.
 *
 * Which fix the message names depends on what is being thrown. For a concrete
 * exception class, declaring it or catching it both resolve the problem. For
 * \Exception and \Throwable neither does: ForbidGenericThrowsTagRule rejects
 * the tag and ForbidBroadCatchRule rejects the catch, so the only way out is a
 * more specific exception class, and the message has to say so rather than send
 * the reader around that loop.
 */
final class MissingThrowsTagErrorBuilder
{
    /**
     * Builds the error for one undeclared thrown class at a throw site.
     */
    public function undeclaredThrow(string $thrownClassName, string $methodName, int $line): IdentifierRuleError
    {
        $lowerClassName = strtolower($thrownClassName);
        $isGeneric = $lowerClassName === 'exception' || $lowerClassName === 'throwable';

        $message = $isGeneric
            ? sprintf(
                'Throw a concrete exception class here instead of \%s, then declare it with "@throws" in the PHPDoc of %s(). Declaring "@throws \%s" is rejected as a generic tag and catching \%s is rejected as a broad catch, so neither of those resolves this.',
                $thrownClassName,
                $methodName,
                $thrownClassName,
                $thrownClassName
            )
            : sprintf(
                'Declare "@throws \%s" in the PHPDoc of %s() or catch the exception inside the method. The exception thrown here escapes %s() without being declared.',
                $thrownClassName,
                $methodName,
                $methodName
            );

        return RuleErrorBuilder::message($message)
            ->identifier('customRules.missingThrowsTag')
            ->line($line)
            ->build();
    }
}
