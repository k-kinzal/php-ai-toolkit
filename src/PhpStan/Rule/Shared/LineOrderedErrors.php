<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\Shared;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\RuleError;

use function usort;

/**
 * Puts the errors of one node in the order their lines are read in.
 *
 * A rule reports what it finds in the order it inspects a node, which is
 * the order of its members rather than the order of the file. PHPStan 2
 * sorts the errors of a run by line before printing them and PHPStan 1
 * prints them as the rule returned them, so a rule that reports several
 * errors sorts them itself and reads the same under either analyzer.
 */
final class LineOrderedErrors
{
    /**
     * The line reported for an error that points at no line.
     */
    public const NO_LINE = 0;

    /**
     * Returns the errors ordered by the line each of them points at.
     *
     * @param list<IdentifierRuleError> $errors
     *
     * @return list<IdentifierRuleError>
     */
    public function sorted(array $errors): array
    {
        usort($errors, fn (RuleError $left, RuleError $right): int => $this->lineOf($left) <=> $this->lineOf($right));

        return $errors;
    }

    /**
     * Returns the line one error points at, or zero when it points at none.
     */
    public function lineOf(RuleError $error): int
    {
        return $error instanceof LineRuleError ? $error->getLine() : self::NO_LINE;
    }
}
