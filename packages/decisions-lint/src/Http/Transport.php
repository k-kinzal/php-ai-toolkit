<?php

declare(strict_types=1);

namespace DecisionsLint\Http;

use DecisionsLint\Diagnostic\LintException;

/**
 * Sends HTTP requests and returns their responses in the same order.
 *
 * Implementations may send the requests concurrently. Tests inject one that
 * answers from memory, so no provider is contacted.
 */
interface Transport
{
    /**
     * @param list<HttpRequest> $requests
     * @return list<HttpResponse>
     * @throws LintException when a request cannot be sent or no response arrives
     */
    public function send(array $requests): array;
}
