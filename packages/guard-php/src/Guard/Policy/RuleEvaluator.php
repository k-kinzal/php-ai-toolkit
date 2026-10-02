<?php

declare(strict_types=1);

namespace Toolkit\Guard\Policy;

use JsonException;
use Toolkit\Guard\Document\DataDocument;
use Toolkit\Guard\Document\Selection;
use Toolkit\Guard\Document\XmlDocument;
use Toolkit\Guard\Reporting\Finding;

/**
 * Applies a rule to the selected value without exposing actual configuration values.
 */
final class RuleEvaluator
{
    /**
     * Checks a field, treating numeric XML text as numbers only for bound assertions.
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function accepts(Rule $rule, DataDocument|XmlDocument $document): bool
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
    public function finding(Rule $rule): Finding
    {
        $expectation = json_encode($rule->assertions, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $instruction = $rule->repairable ? 'Run guard apply to set the configured repair.' : 'Set a compliant value or add a repair value satisfying every assertion.';
        return new Finding($rule->file, $rule->id, $rule->level, $rule->select . ' must satisfy ' . $expectation . '. ' . $instruction);
    }
}
