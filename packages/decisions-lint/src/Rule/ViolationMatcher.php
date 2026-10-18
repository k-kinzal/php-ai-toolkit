<?php

declare(strict_types=1);

namespace DecisionsLint\Rule;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Question;
use DecisionsLint\Diagnostic\Finding;
use DecisionsLint\Source\Target;

/**
 * Turns the model's answer about a target into a finding when the rule says that answer violates it.
 */
final class ViolationMatcher
{
    /**
     * Creates a matcher, optionally with a shared template renderer.
     */
    public function __construct(private ?Template $template = null)
    {
    }

    /**
     * Returns the finding for the first violation the answer satisfies, or null when the target passes.
     */
    public function match(Rule $rule, Target $target, Answer $answer): ?Finding
    {
        if ($answer->type !== $rule->decision->type) {
            return null;
        }
        foreach ($rule->violations as $violation) {
            if ($this->violates($rule->decision, $violation, $answer)) {
                return $this->finding($rule, $target, $violation, $answer);
            }
        }

        return null;
    }

    /**
     * Tells whether an answer is the one a violation describes, with at least the threshold's certainty.
     */
    public function violates(DecisionSpec $decision, Violation $violation, Answer $answer): bool
    {
        if ($answer->type === Question::PREDICATE) {
            $probability = $violation->answer === true ? $answer->probability : 1.0 - $answer->probability;

            return $probability >= $decision->threshold;
        }
        if ($answer->confidence < $decision->threshold) {
            return false;
        }
        if ($answer->type === Question::CHOICE) {
            return $answer->choice === $violation->answer;
        }

        return ($violation->minScore === null || $answer->score >= $violation->minScore)
            && ($violation->maxScore === null || $answer->score <= $violation->maxScore);
    }

    /**
     * Renders the message and fix of a violation for a target and answer.
     */
    public function finding(Rule $rule, Target $target, Violation $violation, Answer $answer): Finding
    {
        $template = $this->template ?? new Template();
        $confidence = $answer->type === Question::PREDICATE ? max($answer->probability, 1.0 - $answer->probability) : $answer->confidence;
        $values = $target->variables() + [
            'rule' => $rule->id,
            'answer' => $this->answerText($answer),
            'probability' => number_format($answer->probability, 2, '.', ''),
            'confidence' => number_format($confidence, 2, '.', ''),
            'choice' => $answer->choice,
            'score' => number_format($answer->score, 2, '.', ''),
            'threshold' => (string) $rule->decision->threshold,
        ];

        return new Finding($target->file, $target->line, $target->column, $rule->id, $rule->severity, $target->qualifiedName, $template->render($violation->message, $values), $template->render($violation->fix, $values), round($confidence, 4));
    }

    /**
     * Returns the answer as text: yes or no for a predicate, the option for a choice, the score otherwise.
     */
    public function answerText(Answer $answer): string
    {
        return match ($answer->type) {
            Question::PREDICATE => $answer->probability >= 0.5 ? 'yes' : 'no',
            Question::CHOICE => $answer->choice,
            default => number_format($answer->score, 2, '.', ''),
        };
    }
}
