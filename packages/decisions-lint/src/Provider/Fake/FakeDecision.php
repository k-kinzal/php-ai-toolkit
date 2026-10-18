<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\Fake;

use DecisionsLint\Decision\Decision;
use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Verdict;

/**
 * A Decision that answers from a script without any provider, for tests of the linter and the reporters.
 */
final class FakeDecision implements Decision
{
    /**
     * Every inquiry decided so far, in order.
     *
     * @var list<Inquiry>
     */
    public array $inquiries = [];

    /**
     * Creates the fake with a script and the model name its verdicts report.
     */
    public function __construct(private ?AnswerScript $script = null, private string $model = 'fake-1.0')
    {
    }

    /**
     * Answers every question of every inquiry from the script.
     *
     * @param list<Inquiry> $inquiries
     * @return list<Verdict>
     */
    public function decide(array $inquiries): array
    {
        $script = $this->script ?? new AnswerScript();
        $verdicts = [];
        foreach ($inquiries as $inquiry) {
            $this->inquiries[] = $inquiry;
            $answers = [];
            foreach ($inquiry->questions as $question) {
                $answers[$question->name] = $script->answer($question, $inquiry->subject);
            }
            $verdicts[] = new Verdict($this->model, $answers);
        }

        return $verdicts;
    }
}
