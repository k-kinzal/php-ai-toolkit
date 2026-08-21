<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\ControlFlow;

use function implode;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

use function sprintf;

/**
 * Builds the PHPStan errors for a dispatch that leaves values of a closed type out.
 *
 * The wording lives here rather than in the rules that report it. A subject whose type
 * carries its own values and a subject reached through a class name are the same mistake
 * seen from two sides, and they have to read the same way. The file is named only by the
 * rule that reports after the analysis, where the error no longer sits in a file PHPStan
 * is reading.
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
     * @param list<string> $labels
     */
    public function buildMatchCatchAll(array $labels, ?string $file, int $line): IdentifierRuleError
    {
        return $this->build(
            sprintf(
                'Match expression sends %s to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                $this->describe($labels),
            ),
            self::CATCH_ALL_IDENTIFIER,
            $file,
            $line,
        );
    }

    /**
     * Builds the error for a switch statement whose "default" case absorbs closed values.
     *
     * @param list<string> $labels
     */
    public function buildSwitchCatchAll(array $labels, ?string $file, int $line): IdentifierRuleError
    {
        return $this->build(
            sprintf(
                'Switch statement sends %s to its "default" case. Write a "case" for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                $this->describe($labels),
            ),
            self::CATCH_ALL_IDENTIFIER,
            $file,
            $line,
        );
    }

    /**
     * Builds the error for a switch statement that handles neither the values nor a "default".
     *
     * @param list<string> $labels
     */
    public function buildSwitchUnhandled(array $labels, ?string $file, int $line): IdentifierRuleError
    {
        return $this->build(
            sprintf(
                'Switch statement does not handle %s. Write a "case" for each of those values: the subject holds a closed set of values and this switch has no "default", so those fall through it unhandled.',
                $this->describe($labels),
            ),
            self::UNHANDLED_IDENTIFIER,
            $file,
            $line,
        );
    }

    /**
     * Builds one error, naming the file when the reporting rule is not reading one.
     */
    public function build(string $message, string $identifier, ?string $file, int $line): IdentifierRuleError
    {
        $builder = RuleErrorBuilder::message($message)
            ->identifier($identifier)
            ->line($line);

        if ($file !== null) {
            $builder = $builder->file($file);
        }

        return $builder->build();
    }

    /**
     * Returns how each value a dispatch left out is written in a message.
     *
     * @param list<Type> $types
     * @return list<string>
     */
    public function labels(array $types): array
    {
        $labels = [];
        foreach ($types as $type) {
            $labels[] = $type->describe(VerbosityLevel::value());
        }

        return $labels;
    }

    /**
     * Returns the reported values as a comma separated list.
     *
     * @param list<string> $labels
     */
    public function describe(array $labels): string
    {
        return implode(', ', $labels);
    }
}
