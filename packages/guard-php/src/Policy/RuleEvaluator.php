<?php

declare(strict_types=1);

namespace Guard\Policy;

use Guard\Document\DataDocument;
use Guard\Document\PhpDocument;
use Guard\Document\Selection;
use Guard\Document\XmlDocument;
use Guard\Reporting\Finding;
use JsonException;

/**
 * Applies a rule to the selected value without exposing actual configuration values.
 */
final class RuleEvaluator
{
    /**
     * Checks a field, treating numeric XML text as numbers only for bound assertions.
     * @throws JsonException
     */
    public function accepts(Rule $rule, DataDocument|XmlDocument|PhpDocument $document): bool
    {
        $selection = $document->read($rule->select);
        if ($document instanceof XmlDocument && $selection->exists && is_string($selection->value)
            && is_numeric($selection->value) && !array_key_exists('equals', $rule->assertions) && !array_key_exists('one_of', $rule->assertions)) {
            $selection = new Selection(true, (float) $selection->value);
        }
        return (new Constraint())->accepts($selection, $rule->assertions);
    }
    /**
     * Describes the rule and its repair without printing the current value.
     * @throws JsonException
     */
    public function finding(Rule $rule, bool $repairAttempted = false): Finding
    {
        $message = (new \Guard\Reporting\FieldMessage())->render($rule);
        if ($repairAttempted && $rule->repairable) {
            $message = $rule->select . ' in ' . $rule->file . ' still violates its constraints after the configured repair. '
                . 'Resolve the conflicting assertions or repairs for this file in guard.yaml before running guard fix again.';
        }
        return new Finding($rule->file, $rule->id, $rule->level, $message, $rule->repairable && !$repairAttempted);
    }
}
