<?php

declare(strict_types=1);

namespace DecisionsLint\Decision;

use DecisionsLint\Diagnostic\LintException;

/**
 * Answers typed questions about subjects.
 *
 * The linter depends on this interface only. An adapter in the Provider layer
 * implements it for each decision API, translating inquiries into that API's
 * requests and its responses back into verdicts.
 */
interface Decision
{
    /**
     * Answers every inquiry, returning one verdict per inquiry in the same order.
     *
     * @param list<Inquiry> $inquiries
     * @return list<Verdict>
     * @throws LintException when the provider rejects a request or cannot be reached
     */
    public function decide(array $inquiries): array;
}
