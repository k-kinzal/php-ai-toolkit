<?php

declare(strict_types=1);

namespace Guard\Policy\Configuration;

use Guard\Collect\Configuration\ConfigurationDocument;
use Guard\Collect\Subject;
use Guard\Document\DocumentFailure;
use Guard\Execution\Context;
use Guard\Execution\FileChange;
use Guard\Execution\Plan;
use Guard\Policy\Policy;
use Guard\Policy\PolicyException;
use Guard\Policy\RuleEvaluator;
use JsonException;
use Nette\Neon\Exception as NeonException;
use RuntimeException;

/**
 * Plans repairs and rechecks all overlapping field constraints before writes.
 */
final class ConfigurationPolicy implements Policy
{
    /**
     * Evaluates a private document copy so other policies see the collected input.
     * @throws JsonException
     * @throws NeonException
     * @throws PolicyException
     */
    public function evaluate(Subject $information, Context $context): Plan
    {
        if (!$information instanceof ConfigurationDocument) {
            throw new PolicyException('ConfigurationPolicy requires ConfigurationDocument. Register it for that information type.');
        }
        try {
            return $this->plan($information, $context->repair);
        } catch (RuntimeException|JsonException|NeonException $exception) {
            throw (new DocumentFailure())->at($information->path, $exception);
        }
    }
    /**
     * Evaluates the collected document and returns proposed bytes without filesystem access.
     * @throws JsonException
     * @throws NeonException
     * @throws PolicyException
     */
    public function plan(ConfigurationDocument $information, bool $repair): Plan
    {
        $document = clone $information->document;
        $evaluator = new RuleEvaluator();
        $changed = false;
        foreach ($information->rules as $rule) {
            if ($rule->format !== $information->format) {
                throw new PolicyException('Conflicting formats for ' . $rule->file . '. Use one format for all its rules.');
            }
            if ($repair && $rule->repairable && !$evaluator->accepts($rule, $document)) {
                $document->write($rule->select, $rule->repair);
                $changed = true;
            }
        }
        $findings = [];
        foreach ($information->rules as $rule) {
            if (!$evaluator->accepts($rule, $document)) {
                $findings[] = $evaluator->finding($rule);
            }
        }
        return new Plan($findings, $changed ? [new FileChange($information->path, $information->source, $document->encode())] : [], $findings);
    }
}
