<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\Fake;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Http\Transport;

/**
 * A transport that replays canned responses in order and records every request, for tests of the HTTP layer.
 */
final class FakeTransport implements Transport
{
    /**
     * Every request sent so far, in order.
     *
     * @var list<HttpRequest>
     */
    public array $requests = [];

    /**
     * Creates the transport with the responses it hands out, one per request.
     *
     * @param list<HttpResponse> $responses
     */
    public function __construct(private array $responses = [])
    {
    }

    /**
     * Records the requests and returns the next canned responses.
     *
     * @param list<HttpRequest> $requests
     * @return list<HttpResponse>
     * @throws LintException when more requests arrive than responses were given
     */
    public function send(array $requests): array
    {
        $responses = [];
        foreach ($requests as $request) {
            $this->requests[] = $request;
            $response = array_shift($this->responses);
            if ($response === null) {
                throw new LintException(sprintf('FakeTransport has no response left for %s %s. Give it one HttpResponse per expected request.', $request->method, $request->url));
            }
            $responses[] = $response;
        }

        return $responses;
    }
}
