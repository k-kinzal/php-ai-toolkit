<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Shared;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\RuleError;

use function strcmp;
use function usort;

/**
 * Puts the errors of one node in PHPStan 2's deterministic order.
 *
 * A rule reports what it finds in the order it inspects a node, which is
 * the order of its members rather than the order of the file. PHPStan 2
 * sorts the errors of a run by line and message before printing them and
 * PHPStan 1 prints them as the rule returned them, so a rule that reports
 * several errors sorts them itself and reads the same under either analyzer.
 */
final class LineOrderedErrors
{
    /**
     * The line reported for an error that points at no line.
     */
    public const NO_LINE = 0;

    /**
     * Returns the errors ordered by line and then message.
     *
     * @param list<IdentifierRuleError> $errors
     *
     * @return list<IdentifierRuleError>
     */
    public function sorted(array $errors): array
    {
        usort($errors, function (RuleError $left, RuleError $right): int {
            $lineOrder = $this->lineOf($left) <=> $this->lineOf($right);

            return $lineOrder !== 0 ? $lineOrder : strcmp($left->getMessage(), $right->getMessage());
        });

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
