<?php

declare(strict_types=1);

namespace DecisionsLint\Rule;

use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use DecisionsLint\Source\Target;

/**
 * Builds the question and the subject a rule sends to the model for one target.
 */
final class QuestionBuilder
{
    /**
     * Creates a builder, optionally with a shared template renderer.
     */
    public function __construct(private ?Template $template = null)
    {
    }

    /**
     * Returns the question of a rule with the target's values filled into the instructions.
     */
    public function question(Rule $rule, Target $target): Question
    {
        $values = $target->variables() + ['rule' => $rule->id, 'threshold' => (string) $rule->decision->threshold];

        return new Question($rule->id, $rule->decision->type, ($this->template ?? new Template())->render($rule->decision->instructions, $values), $rule->decision->choices, $rule->decision->levels);
    }

    /**
     * Returns the named sections the model reads about a target, with the source its context mode asks for.
     *
     * @return array<string, string>
     */
    public function subject(Target $target, string $context): array
    {
        $subject = [
            'file' => $target->file,
            'kind' => $target->kind,
            'name' => $target->qualifiedName,
            'signature' => $target->signature,
            'declaration' => $target->declaration,
        ];
        if ($context !== 'declaration') {
            $subject[$context === 'class' ? 'class_source' : 'file_source'] = $target->context($context);
        }

        return $subject;
    }

    /**
     * Returns one inquiry for a target with every question of the given rules.
     *
     * @param list<Rule> $rules
     */
    public function inquiry(Target $target, string $context, array $rules): Inquiry
    {
        $questions = [];
        foreach ($rules as $rule) {
            $questions[] = $this->question($rule, $target);
        }

        return new Inquiry($this->subject($target, $context), $questions);
    }
}
