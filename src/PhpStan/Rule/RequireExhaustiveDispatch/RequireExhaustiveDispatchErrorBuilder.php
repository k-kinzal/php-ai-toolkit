<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\RequireExhaustiveDispatch;

use function implode;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

use function sprintf;

/**
 * Builds the PHPStan errors for a dispatch that leaves values of a closed type out.
 */
final class RequireExhaustiveDispatchErrorBuilder
{
    /**
     * The identifier of a dispatch that names no branch for a value and has no catch-all either.
     */
    public const UNHANDLED_IDENTIFIER = 'customRules.exhaustiveDispatch';

    /**
     * The identifier of a dispatch whose catch-all branch absorbs values of a closed type.
     */
    public const CATCH_ALL_IDENTIFIER = 'customRules.exhaustiveDispatchDefault';

    /**
     * Builds the error for a match expression whose "default" arm absorbs closed values.
     *
     * @param list<Type> $unhandled
     */
    public function buildMatchCatchAll(array $unhandled, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Match expression sends %s to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                $this->describe($unhandled),
            )
        )
            ->identifier(self::CATCH_ALL_IDENTIFIER)
            ->line($line)
            ->build();
    }

    /**
     * Builds the error for a switch statement whose "default" case absorbs closed values.
     *
     * @param list<Type> $unhandled
     */
    public function buildSwitchCatchAll(array $unhandled, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Switch statement sends %s to its "default" case. Write a "case" for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                $this->describe($unhandled),
            )
        )
            ->identifier(self::CATCH_ALL_IDENTIFIER)
            ->line($line)
            ->build();
    }

    /**
     * Builds the error for a switch statement that handles neither the values nor a "default".
     *
     * @param list<Type> $unhandled
     */
    public function buildSwitchUnhandled(array $unhandled, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Switch statement does not handle %s. Write a "case" for each of those values: the subject holds a closed set of values and this switch has no "default", so those fall through it unhandled.',
                $this->describe($unhandled),
            )
        )
            ->identifier(self::UNHANDLED_IDENTIFIER)
            ->line($line)
            ->build();
    }

    /**
     * Returns the reported values as a comma separated list.
     *
     * @param list<Type> $unhandled
     */
    public function describe(array $unhandled): string
    {
        $labels = [];
        foreach ($unhandled as $type) {
            $labels[] = $type->describe(VerbosityLevel::value());
        }

        return implode(', ', $labels);
    }
}
