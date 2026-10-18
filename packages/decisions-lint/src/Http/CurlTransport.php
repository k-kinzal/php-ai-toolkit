<?php

declare(strict_types=1);

namespace DecisionsLint\Http;

use CurlHandle;
use CurlMultiHandle;
use DecisionsLint\Diagnostic\LintException;

/**
 * Sends requests over cURL, several at a time, so a lint run is bounded by the provider and not by one connection.
 */
final class CurlTransport implements Transport
{
    /**
     * Creates a transport that keeps at most a number of requests in flight and gives up on each after a timeout.
     */
    public function __construct(private int $concurrency = 8, private int $timeout = 60)
    {
    }

    /**
     * Sends every request, at most the configured number at a time, and returns the responses in request order.
     *
     * @param list<HttpRequest> $requests
     * @return list<HttpResponse>
     * @throws LintException when a request fails before a response arrives
     */
    public function send(array $requests): array
    {
        $responses = [];
        foreach (array_chunk($requests, max(1, $this->concurrency), true) as $chunk) {
            foreach ($this->sendChunk($chunk) as $index => $response) {
                $responses[$index] = $response;
            }
        }
        ksort($responses);

        return array_values($responses);
    }

    /**
     * Sends one group of requests concurrently and returns the responses keyed like the requests.
     *
     * @param array<int, HttpRequest> $requests
     * @return array<int, HttpResponse>
     * @throws LintException when a request fails before a response arrives
     */
    public function sendChunk(array $requests): array
    {
        $multi = curl_multi_init();
        $handles = [];
        foreach ($requests as $index => $request) {
            $handles[$index] = $this->handle($request);
            curl_multi_add_handle($multi, $handles[$index]);
        }
        $results = $this->drive($multi);
        $responses = [];
        foreach ($handles as $index => $handle) {
            $responses[$index] = $this->response($handle, $requests[$index], $results[spl_object_id($handle)] ?? CURLE_OK);
            curl_multi_remove_handle($multi, $handle);
        }
        curl_multi_close($multi);

        return $responses;
    }

    /**
     * Builds the cURL handle for one request.
     */
    public function handle(HttpRequest $request): CurlHandle
    {
        $handle = curl_init();
        $headers = [];
        foreach ($request->headers as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }
        curl_setopt($handle, CURLOPT_URL, $request->url);
        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $request->method);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $request->body);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, min(10, $this->timeout));
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, false);

        return $handle;
    }

    /**
     * Runs the multi handle until every transfer has finished and returns each transfer's cURL result code by handle object id.
     *
     * @return array<int, int>
     */
    public function drive(CurlMultiHandle $multi): array
    {
        $results = [];
        do {
            $status = curl_multi_exec($multi, $running);
            while (($message = curl_multi_info_read($multi)) !== false) {
                $handle = $message['handle'] ?? null;
                $result = $message['result'] ?? null;
                if ($handle instanceof CurlHandle && is_int($result)) {
                    $results[spl_object_id($handle)] = $result;
                }
            }
            if ($running > 0) {
                curl_multi_select($multi, 1.0);
            }
        } while ($running > 0 && $status === CURLM_OK);

        return $results;
    }

    /**
     * Reads the response of a finished handle.
     *
     * @param int $result the cURL result code of the transfer, CURLE_OK when it completed
     * @throws LintException when the transfer failed before a response arrived
     */
    public function response(CurlHandle $handle, HttpRequest $request, int $result): HttpResponse
    {
        $body = curl_multi_getcontent($handle);
        $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        if ($result !== CURLE_OK || $body === null || $status === 0) {
            $reason = $result === CURLE_OK ? 'no response' : (curl_strerror($result) ?? 'cURL error ' . $result);
            throw new LintException(sprintf('Could not reach %s: %s. Check the network connection, provider.base_url and provider.timeout in decisions.yaml.', $request->url, $reason));
        }

        return new HttpResponse($status, $body);
    }
}
